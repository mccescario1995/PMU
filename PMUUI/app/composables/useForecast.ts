import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, watch, ref, reactive, computed } from 'vue'
import { usePermissions } from '~/composables/usePermissions'
import { useToast } from '#imports'

export function useForecast(endpoint: string = '/v1/forecasts', model: string = '') {
  const { can } = usePermissions()
  const toast = useToast()

  const forecasts = ref<any[]>([])
  const loading = ref(true)
  const showForm = ref(false)
  const modelLoading = ref(false)
  const modelError = ref("")

  const modalMode = ref<'create' | 'edit' | 'view'>('create')
  const saving = ref(false)
  const editingForecast = ref<any>(null)
  const viewing = ref(false)

  const form = reactive({
    forecast_date: new Date().toISOString().slice(0, 10),
    predicted_revenue: 0,
    season: "",
    model_version: "",
    weather: null as any,
  })

  const currency = (value: number) =>
    new Intl.NumberFormat("en-US", { style: "currency", currency: "PHP" }).format(value)

  async function load() {
    loading.value = true
    try {
      forecasts.value = (await apiFetch(endpoint, { parseJson: true })) as any[]
    } finally {
      loading.value = false
    }
  }

  async function loadWeather(date: string) {
    try {
      const w = await apiFetch(`/v1/weather?date=${date}`, { parseJson: true }) as any[]
      form.weather = w && w.length ? w[0] : null
    } catch {
      form.weather = null
    }
  }

  watch(() => form.forecast_date, (date) => {
    if (date) loadWeather(date)
  })

  function reset() {
    form.forecast_date = new Date().toISOString().slice(0, 10)
    form.predicted_revenue = 0
    form.season = ""
    form.model_version = ""
    form.weather = null
    editingForecast.value = null
    viewing.value = false
    modalMode.value = 'create'
    showForm.value = false
  }

  function openCreate() {
    reset()
    showForm.value = true
  }

  function openView(row: any) {
    modalMode.value = 'view'
    editingForecast.value = row
    viewing.value = true
    form.forecast_date = row.forecast_date?.slice(0, 10) ?? ""
    form.predicted_revenue = row.predicted_revenue ?? 0
    form.season = row.season ?? ""
    form.model_version = row.model_version ?? ""
    form.weather = row.weather ?? null
    showForm.value = true
  }

  function openEdit(row: any) {
    modalMode.value = 'edit'
    editingForecast.value = row
    viewing.value = false
    form.forecast_date = row.forecast_date?.slice(0, 10) ?? ""
    form.predicted_revenue = row.predicted_revenue ?? 0
    form.season = row.season ?? ""
    form.model_version = row.model_version ?? ""
    form.weather = row.weather ?? null
    showForm.value = true
  }

  async function submit() {
    saving.value = true
    try {
      if (modalMode.value === 'edit' && editingForecast.value) {
        await apiFetch(`/v1/forecasts/${editingForecast.value.id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            forecast_date: form.forecast_date,
            predicted_revenue: Number(form.predicted_revenue),
            season: form.season || null,
            model_version: form.model_version || null,
          }),
          parseJson: true,
          throwOnError: true,
        })
        toast.add({ title: 'Forecast updated', color: 'success' })
      } else {
        await apiFetch('/v1/forecasts/generate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            forecast_date: form.forecast_date,
            predicted_revenue: Number(form.predicted_revenue),
            season: form.season || null,
            model_version: form.model_version || null,
          }),
          parseJson: true,
          throwOnError: true,
        })
        toast.add({ title: 'Forecast created', color: 'success' })
      }
      reset()
      await load()
    } catch (e: any) {
      toast.add({
        title: modalMode.value === 'edit' ? 'Failed to update forecast' : 'Failed to create forecast',
        description: e?.body?.message ?? e.message ?? 'Please try again.',
        color: 'error',
      })
    } finally {
      saving.value = false
    }
  }

  const showProgressModal = ref(false)
  const progressSteps = ref<string[]>([])
  const progressError = ref("")

  function addProgressStep(step: string) {
    progressSteps.value.push(step)
  }

  function clearProgress() {
    progressSteps.value = []
    progressError.value = ""
  }

  async function runModel(model: string) {
    modelLoading.value = true
    modelError.value = ""
    clearProgress()
    showProgressModal.value = true

    addProgressStep(`Initializing ${model} model...`)

    const days = (model === "sarima" || model === "samira") ? 180 : 365
    const pmuModel = model === 'arima' ? 'amira' : model === 'sarima' ? 'samira' : model

    // Try direct PMUML call first, fallback to Laravel proxy
    let pmumlResponse: any = null
    let usedFallback = false

    const tryDirectPmuml = async (): Promise<any> => {
      const pmumlUrl = 'https://pmuml.onrender.com/forecast'
      return await $fetch(`${pmumlUrl}?model=${pmuModel}&days=${days}&post_to_api=false`, {
        method: 'POST',
        body: {},
        timeout: 300000,
      })
    }

    const tryLaravelProxy = async (): Promise<any> => {
      // Use Laravel's train endpoint with sync=true to get results directly
      const response = await apiFetch('/v1/forecasts/run-model', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ model, days, sync: true }),
        parseJson: true,
        throwOnError: true,
        timeout: 300000,
      }) as any
      return response
    }

    try {
      addProgressStep("Fetching historical data from PMUML...")
      
      // Try direct call with retry
      let lastError: any = null
      for (let attempt = 1; attempt <= 2; attempt++) {
        try {
          pmumlResponse = await tryDirectPmuml()
          break
        } catch (e: any) {
          lastError = e
          if (attempt < 2) {
            addProgressStep(`Retrying... (attempt ${attempt + 1})`)
            await new Promise(r => setTimeout(r, 2000))
          }
        }
      }

      // Fallback to Laravel proxy if direct call fails
      if (!pmumlResponse && lastError) {
        addProgressStep("Direct PMUML call failed, trying Laravel proxy...")
        usedFallback = true
        try {
          pmumlResponse = await tryLaravelProxy()
        } catch (fallbackError: any) {
          throw new Error(`Both direct and proxy calls failed: ${fallbackError.message}`)
        }
      }

      if (!pmumlResponse) {
        throw new Error('Failed to get response from PMUML')
      }

      addProgressStep("Training model...")
      
      if (!pmumlResponse?.forecasts || !Array.isArray(pmumlResponse.forecasts)) {
        throw new Error('Invalid PMUML response format')
      }

      addProgressStep("Generating forecasts...")

      const saved = []
      for (const item of pmumlResponse.forecasts) {
        const forecastDate = item.date
        const predicted = item.predicted_revenue
        
        if (!forecastDate || predicted === null) continue

        const month = new Date(forecastDate).getMonth() + 1
        const peakMonths = [11, 12, 1, 2, 3, 4]
        const season = peakMonths.includes(month) ? 'Peak' : 'Off-Peak'

        const response = await apiFetch('/v1/forecasts/generate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            forecast_date: forecastDate,
            predicted_revenue: Number(predicted),
            season,
            model_version: `${model}-v1`,
          }),
          parseJson: true,
          throwOnError: true,
        })
        saved.push(response)
      }

      addProgressStep(`Model ${model.toUpperCase()} trained successfully!`)
      addProgressStep(`Generated ${saved.length} forecast records`)

      if (pmumlResponse?.metrics) {
        const metrics = pmumlResponse.metrics
        const metricStrs = Object.entries(metrics).map(([k, v]) => `${k}: ${v}`)
        if (metricStrs.length) {
          addProgressStep(`Metrics: ${metricStrs.join(', ')}`)
        }
      }

      await load()

      setTimeout(() => {
        showProgressModal.value = false
      }, 2000)
    } catch (e: any) {
      progressError.value = e?.message || "Failed to run model"
      addProgressStep(`Error: ${progressError.value}`)
    } finally {
      modelLoading.value = false
    }
  }

  async function remove(row: any) {
    if (!confirm('Delete this forecast?')) return
    const deleteUrl = model
      ? `/v1/forecasts/${row.id}?model=${model}`
      : `/v1/forecasts/${row.id}`
    await apiFetch(deleteUrl, { method: 'DELETE' })
    forecasts.value = forecasts.value.filter((f: any) => f.id !== row.id)
    toast.add({ title: 'Forecast deleted', color: 'success' })
  }



  const weatherLabel = (w: any) => {
    if (!w) return "N/A"
    const parts: string[] = []
    if (w.rainfall_mm !== null && w.rainfall_mm !== undefined) parts.push(`${w.rainfall_mm}mm rain`)
    if (w.temperature !== null && w.temperature !== undefined) parts.push(`${w.temperature}°C`)
    if (w.wind_speed !== null && w.wind_speed !== undefined) parts.push(`${w.wind_speed}km/h wind`)
    return parts.length ? parts.join(', ') : "N/A"
  }

  const totalRevenue = computed(() => forecasts.value.reduce((sum, f) => sum + Number(f.predicted_revenue ?? 0), 0))
  const periods = computed(() => forecasts.value.length)
  const latestModel = computed(() => forecasts.value[0]?.model_version ?? "-")

  const columns: TableColumn<any>[] = [
    {
      accessorKey: 'forecast_date',
      header: 'Period',
      cell: ({ row }) => new Date(row.getValue('forecast_date')).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }),
    },
    {
      accessorKey: 'predicted_revenue',
      header: 'Projected Revenue',
      meta: { class: { th: "text-right", td: "text-right font-mono" } },
      cell: ({ row }) => currency(Number(row.getValue("predicted_revenue"))),
    },
    {
      accessorKey: 'season',
      header: 'Season',
      cell: ({ row }) => row.getValue("season"),
    },
    // {
    //   header: 'Weather',
    //   cell: ({ row }) => row.original.weather ? weatherLabel(row.original.weather) : "No data",
    // },
    { accessorKey: 'action', header: 'Action' },
  ]

  onMounted(() => {
    load()
    loadWeather(form.forecast_date)
  })

  return {
    forecasts,
    loading,
    showForm,
    modelLoading,
    modelError,
    showProgressModal,
    progressSteps,
    progressError,
    form,
    modalMode,
    saving,
    viewing,
    reset,
    submit,
    runModel,
    remove,
    openCreate,
    openView,
    openEdit,
    weatherLabel,
    currency,
    totalRevenue,
    periods,
    latestModel,
    columns,
    can,
    load,
  }
}