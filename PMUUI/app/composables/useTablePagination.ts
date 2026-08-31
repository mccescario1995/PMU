import { ref, computed, watch } from "vue";

type FetchDataOptions = {
  fetchData?: (page: number, pageSize: number) => Promise<{ data: any[]; total?: number; meta?: { total?: number } }>;
};

export function useTablePagination(total: (() => number) | null = null, defaultPageSize = 10, options: FetchDataOptions = {}) {
  const page = ref(1);
  const pageSize = ref<any>(String(defaultPageSize));
  const goToPageInput = ref(1);
  const data = ref<any[]>([]);
  const totalItems = ref(0);
  const loading = ref(false);

  const pageSizeNumber = computed(() => Number(pageSize.value));

  const tablePagination = computed(() => ({
    pageIndex: page.value - 1,
    pageSize: pageSizeNumber.value,
  }));

  const totalPages = computed(() => {
    const count = options.fetchData ? totalItems.value : (total ? total() : 0);
    return Math.max(1, Math.ceil(count / pageSizeNumber.value));
  });

  watch(page, (newPage) => {
    goToPageInput.value = newPage;
  });

  watch(pageSize, () => {
    page.value = 1;
    goToPageInput.value = 1;
  });

  let requestId = 0;

  async function loadData() {
    if (!options.fetchData) return;
    const currentId = ++requestId;
    loading.value = true;
    try {
      const result = await options.fetchData(page.value, pageSizeNumber.value);
      if (currentId === requestId) {
        data.value = result.data;
        totalItems.value = result.meta?.total ?? result.total ?? 0;
      }
    } finally {
      if (currentId === requestId) {
        loading.value = false;
      }
    }
  }

  if (options.fetchData) {
    watch([page, pageSizeNumber], loadData);
    loadData();
  }

  function handleGoToPage() {
    const target = Math.min(Math.max(1, goToPageInput.value), totalPages.value);
    page.value = target;
    goToPageInput.value = target;
  }

  function refresh() {
    loadData();
  }

  return {
    page,
    pageSize,
    pageSizeNumber,
    goToPageInput,
    data,
    totalItems,
    loading,
    tablePagination,
    totalPages,
    handleGoToPage,
    refresh,
  };
}
