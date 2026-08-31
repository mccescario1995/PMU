import type { TableColumn } from '@nuxt/ui'
import { apiFetch } from '~/composables/useApiFetch'
import { onMounted, watch, ref, reactive, computed } from 'vue'
import { usePermissions } from '~/composables/usePermissions'
import { useToast } from '#imports'

export function useForecast() {
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
      forecasts.value = (await apiFetch('/v1/forecasts', { parseJson: true })) as any[]
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

  async function runModel(model: string) {
    modelLoading.value = true
    modelError.value = ""
    try {
      await apiFetch(`/v1/forecasts/run-model`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ model, days: 30 }),
        parseJson: true,
        throwOnError: true,
      })
      await load()
    } catch (e: any) {
      modelError.value = e?.message || "Failed to run model"
    } finally {
      modelLoading.value = false
    }
  }

  async function remove(row: any) {
    if (!confirm('Delete this forecast?')) return
    await apiFetch(`/v1/forecasts/${row.id}`, { method: 'DELETE' })
    forecasts.value = forecasts.value.filter((f: any) => f.id !== row.id)
    toast.add({ title: 'Forecast deleted', color: 'success' })
  }

  const weatherLabel = (w: any) => {
    if (!w) return "No weather data"
    const parts: string[] = []
    if (w.rainfall_mm !== null && w.rainfall_mm !== undefined) parts.push(`${w.rainfall_mm}mm rain`)
    if (w.temperature !== null && w.temperature !== undefined) parts.push(`${w.temperature}°C`)
    if (w.wind_speed !== null && w.wind_speed !== undefined) parts.push(`${w.wind_speed}km/h wind`)
    return parts.length ? parts.join(', ') : "No weather data"
  }

  const totalRevenue = computed(() => forecasts.value.reduce((sum, f) => sum + Number(f.predicted_revenue ?? 0), 0))
  const periods = computed(() => forecasts.value.length)
  const latestModel = computed(() => forecasts.value[0]?.model_version ?? "-")

  const columns: TableColumn<any>[] = [
    {
      accessorKey: 'forecast_date',
      header: 'Period',
      cell: ({ row }) => new Date(row.getValue('forecast_date')).toLocaleDateString('en-US', { year: 'numeric', month: 'short' }),
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
    {
      header: 'Weather',
      cell: ({ row }) => row.original.weather ? weatherLabel(row.original.weather) : "No data",
    },
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
