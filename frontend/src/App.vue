<template>
  <div class="min-h-screen app-background text-slate-900">
    <main class="max-w-4xl mx-auto px-4 py-8">
      <RouterView v-slot="{ Component, route }">
        <Transition :name="getTransitionName(route)" mode="out-in">
          <component :is="Component" :key="route.path" />
        </Transition>
      </RouterView>
    </main>
  </div>
</template>

<script setup>
import { RouterView } from 'vue-router'

function getTransitionName(route) {
  // Title -> Quiz のときはスライド演出
  if (route.name === 'Quiz') {
    return 'slide-left'
  }
  // Result -> Title のときは下からスライド
  if (route.name === 'Title') {
    return 'slide-down'
  }
  // その他はフェード
  return 'fade'
}
</script>

<style>
.app-background {
  position: relative;
}

.app-background::before {
  content: '';
  position: fixed;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, hsl(var(--primary) / 0.03) 1px, transparent 1px);
  background-size: 40px 40px;
  animation: backgroundMove 60s linear infinite;
  pointer-events: none;
  z-index: -1;
}

@keyframes backgroundMove {
  0% {
    transform: translate(0, 0);
  }
  100% {
    transform: translate(40px, 40px);
  }
}

/* Slide Left Transition (Title -> Quiz) */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-left-enter-from {
  transform: translateX(30px);
  opacity: 0;
}

.slide-left-enter-to {
  transform: translateX(0);
  opacity: 1;
}

.slide-left-leave-from {
  transform: translateX(0);
  opacity: 1;
}

.slide-left-leave-to {
  transform: translateX(-30px);
  opacity: 0;
}

/* Slide Down Transition (Result -> Title) */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-down-enter-from {
  transform: translateY(-20px);
  opacity: 0;
}

.slide-down-enter-to {
  transform: translateY(0);
  opacity: 1;
}

.slide-down-leave-from {
  transform: translateY(0);
  opacity: 1;
}

.slide-down-leave-to {
  transform: translateY(20px);
  opacity: 0;
}

/* Fade Transition (Default) */
.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

.fade-enter-to,
.fade-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>
