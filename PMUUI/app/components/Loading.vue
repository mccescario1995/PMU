<script setup lang="ts">
interface Props {
  size?: number;
  color?: string;
  thickness?: number;
}

const props = withDefaults(defineProps<Props>(), {
  size: 24,
  color: "currentColor",
  thickness: 3,
});
</script>

<template>
  <div class="loading-wrapper" :style="wrapperStyle">
    <svg class="loading-spinner" :style="spinnerStyle" viewBox="0 0 50 50">
      <circle
        class="loading-path"
        :style="pathStyle"
        cx="25"
        cy="25"
        r="20"
        fill="none"
        stroke-width="4"
        stroke-miterlimit="10"
      />
    </svg>
  </div>
</template>

<script setup lang="ts">
const wrapperStyle = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
  display: "inline-flex",
  alignItems: "center",
  justifyContent: "center",
}));

const spinnerStyle = computed(() => ({
  animation: "loading-rotate 1.4s linear infinite",
  width: "100%",
  height: "100%",
}));

const pathStyle = computed(() => ({
  stroke: props.color,
  strokeWidth: `${props.thickness}px`,
  strokeDasharray: "90, 150",
  strokeDashoffset: "0",
  animation: "loading-dash 1.4s ease-in-out infinite",
  strokeLinecap: "round",
}));
</script>

<style scoped>
@keyframes loading-rotate {
  100% {
    transform: rotate(360deg);
  }
}

@keyframes loading-dash {
  0% {
    stroke-dasharray: 1, 150;
    stroke-dashoffset: 0;
  }
  50% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -35;
  }
  100% {
    stroke-dasharray: 90, 150;
    stroke-dashoffset: -124;
  }
}

.loading-wrapper {
  flex-shrink: 0;
}

.loading-spinner {
  transform-origin: center center;
}
</style>