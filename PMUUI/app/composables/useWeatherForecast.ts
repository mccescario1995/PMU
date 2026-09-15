import { ref, onMounted } from "vue";

interface DailyForecast {
  date: string;
  tempMax: number | null;
  tempMin: number | null;
  precipitation: number | null;
  windSpeed: number | null;
}

export function useWeatherForecast() {
  const daily = ref<DailyForecast[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const lat = 13.5049;
      const lon = 123.0434;
      const url =
        `https://api.open-meteo.com/v1/forecast?` +
        `latitude=${lat}&longitude=${lon}` +
        `&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max` +
        `&timezone=Asia%2FManila&forecast_days=7`;

      const res = await fetch(url);
      if (!res.ok) throw new Error(`Open-Meteo request failed: ${res.status}`);
      const data = await res.json();

      const dailyData = data.daily;
      const dates: string[] = dailyData.time;
      const tempMax: (number | null)[] = dailyData.temperature_2m_max || [];
      const tempMin: (number | null)[] = dailyData.temperature_2m_min || [];
      const precip: (number | null)[] = dailyData.precipitation_sum || [];
      const wind: (number | null)[] = dailyData.wind_speed_10m_max || [];

      daily.value = dates.map((date, i) => ({
        date,
        tempMax: tempMax[i] ?? null,
        tempMin: tempMin[i] ?? null,
        precipitation: precip[i] ?? null,
        windSpeed: wind[i] ?? null,
      }));
    } catch (e: any) {
      error.value = e.message ?? "Failed to load weather forecast";
    } finally {
      loading.value = false;
    }
  }

  onMounted(() => {
    load();
  });

  return { daily, loading, error, load };
}
