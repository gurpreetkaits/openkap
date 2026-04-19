<template>
  <div class="p-4 space-y-5">

    <!-- ─── Background ─── -->
    <section class="bg-gray-50 rounded-xl p-3.5">
      <button @click="bgOpen = !bgOpen" class="sec-header">
        <span>Background</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="bgOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div v-if="bgOpen" class="space-y-3 mt-2">
        <!-- Type pills -->
        <div class="flex p-0.5 bg-gray-100 rounded-lg">
          <button v-for="t in ['none','solid','gradient','image']" :key="t" @click="styleBackground.type = t"
            :class="styleBackground.type === t ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
            class="flex-1 py-1.5 text-[11px] font-medium rounded-md transition-all capitalize">{{ t === 'none' ? 'Off' : t }}</button>
        </div>

        <!-- Solid -->
        <div v-if="styleBackground.type === 'solid'" class="space-y-2">
          <div class="grid grid-cols-8 gap-1.5">
            <button v-for="c in bgPresetColors" :key="c" @click="styleBackground.color = c"
              class="w-full aspect-square rounded-lg border-2 transition-all hover:scale-110"
              :class="styleBackground.color === c ? 'border-orange-500 ring-2 ring-orange-200' : 'border-gray-200'"
              :style="{ backgroundColor: c }"></button>
          </div>
          <div class="flex items-center gap-2">
            <input type="color" v-model="styleBackground.color" class="w-8 h-8 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
            <input type="text" v-model="styleBackground.color" class="flex-1 px-2 py-1.5 text-[11px] font-mono bg-gray-50 rounded-lg border border-gray-200 outline-none focus:border-orange-400 text-gray-700" />
          </div>
        </div>

        <!-- Gradient -->
        <div v-if="styleBackground.type === 'gradient'" class="space-y-2">
          <div class="grid grid-cols-6 gap-1.5">
            <button v-for="(g, i) in bgPresetGradients" :key="i"
              @click="styleBackground.gradientFrom = g.from; styleBackground.gradientTo = g.to; styleBackground.gradientDirection = g.dir"
              class="w-full aspect-square rounded-lg border-2 transition-all hover:scale-110"
              :class="styleBackground.gradientFrom === g.from && styleBackground.gradientTo === g.to ? 'border-orange-500 ring-2 ring-orange-200' : 'border-gray-200'"
              :style="{ background: `linear-gradient(${g.dir === 'br' ? '135deg' : '180deg'}, ${g.from}, ${g.to})` }"></button>
          </div>
          <div class="flex items-center gap-2">
            <input type="color" v-model="styleBackground.gradientFrom" class="w-7 h-7 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            <input type="color" v-model="styleBackground.gradientTo" class="w-7 h-7 rounded-lg border border-gray-200 cursor-pointer p-0.5" />
            <select v-model="styleBackground.gradientDirection" class="flex-1 text-[11px] px-2 py-1.5 bg-gray-50 rounded-lg border border-gray-200 outline-none text-gray-700">
              <option value="b">Vertical</option>
              <option value="br">Diagonal</option>
              <option value="r">Horizontal</option>
            </select>
          </div>
        </div>

        <!-- Image -->
        <div v-if="styleBackground.type === 'image'" class="space-y-2">
          <div class="grid grid-cols-4 gap-1.5">
            <button v-for="(url, i) in bgPresetImages" :key="i"
              @click="styleBackground.imageUrl = url"
              class="w-full aspect-square rounded-lg border-2 overflow-hidden transition-all hover:scale-105"
              :class="styleBackground.imageUrl === url ? 'border-orange-500 ring-2 ring-orange-200' : 'border-gray-200'"
            >
              <img :src="url" class="w-full h-full object-cover" loading="lazy" />
            </button>
          </div>
          <label class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-200 rounded-xl text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-xs font-medium">Upload Image</span>
            <input type="file" accept="image/*" class="hidden" @change="handleBgImageUpload" />
          </label>
        </div>
      </div>
    </section>

    <!-- ─── Video Frame ─── -->
    <section class="bg-gray-50 rounded-xl p-3.5">
      <button @click="frameOpen = !frameOpen" class="sec-header">
        <span>Video Frame</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="frameOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div v-if="frameOpen" class="space-y-3 mt-2">
        <div>
          <label class="slider-label"><span>Padding</span><span class="font-mono text-gray-500">{{ stylePadding }}px</span></label>
          <input type="range" v-model.number="stylePadding" min="0" max="120" step="4" class="slider" />
        </div>
        <div>
          <label class="slider-label"><span>Roundness</span><span class="font-mono text-gray-500">{{ styleRoundness }}px</span></label>
          <input type="range" v-model.number="styleRoundness" min="0" max="32" step="1" class="slider" />
        </div>
        <label class="flex items-center justify-between cursor-pointer py-1">
          <span class="text-xs text-gray-600">Drop Shadow</span>
          <div class="relative">
            <input type="checkbox" v-model="styleShadow" class="sr-only peer" />
            <div class="w-9 h-5 bg-gray-200 peer-checked:bg-orange-500 rounded-full transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-4 transition-transform"></div>
          </div>
        </label>
      </div>
    </section>

    <!-- ─── Camera ─── -->
    <section v-if="video?.has_camera" class="bg-gray-50 rounded-xl p-3.5">
      <button @click="camOpen = !camOpen" class="sec-header">
        <span>Camera</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="camOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div v-if="camOpen" class="space-y-3 mt-2">
        <label class="flex items-center justify-between cursor-pointer py-1">
          <span class="text-xs text-gray-600 font-medium">Show Camera</span>
          <div class="relative">
            <input type="checkbox" v-model="cameraEnabled" class="sr-only peer" />
            <div class="w-9 h-5 bg-gray-200 peer-checked:bg-orange-500 rounded-full transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow peer-checked:translate-x-4 transition-transform"></div>
          </div>
        </label>

        <template v-if="cameraEnabled">
          <div>
            <label class="text-[10px] text-gray-500 block mb-1.5 font-medium uppercase tracking-wider">Position</label>
            <div class="grid grid-cols-2 gap-1.5 w-20">
              <button v-for="pos in ['top-left','top-right','bottom-left','bottom-right']" :key="pos" @click="cameraPosition = pos"
                :class="cameraPosition === pos ? 'bg-orange-500' : 'bg-gray-200 hover:bg-gray-300'"
                class="w-full aspect-square rounded-md transition-colors"></button>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-gray-500 block mb-1.5 font-medium uppercase tracking-wider">Shape</label>
            <div class="flex p-0.5 bg-gray-100 rounded-lg">
              <button v-for="s in ['portrait','circle','square']" :key="s" @click="cameraShape = s"
                :class="cameraShape === s ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
                class="flex-1 py-1.5 text-[11px] font-medium rounded-md transition-all capitalize">{{ s }}</button>
            </div>
          </div>

          <div>
            <label class="slider-label"><span>Size</span><span class="font-mono text-gray-500">{{ cameraSize }}%</span></label>
            <input type="range" v-model.number="cameraSize" min="10" max="50" step="1" class="slider" />
          </div>

          <div>
            <label class="slider-label"><span>Roundness</span><span class="font-mono text-gray-500">{{ cameraRoundness }}px</span></label>
            <input type="range" v-model.number="cameraRoundness" min="0" max="50" step="1" class="slider" />
          </div>
        </template>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useEditorState } from '@/composables/useEditorState'

const {
  video,
  styleBackground, stylePadding, styleRoundness, styleShadow,
  cameraEnabled, cameraPosition, cameraSize, cameraRoundness, cameraShape,
  bgPresetColors, bgPresetGradients, bgPresetImages,
} = useEditorState()

const bgOpen = ref(true)
const frameOpen = ref(true)
const camOpen = ref(true)

function handleBgImageUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = (ev) => {
    styleBackground.value.imageUrl = ev.target.result
    styleBackground.value.type = 'image'
  }
  reader.readAsDataURL(file)
  e.target.value = ''
}
</script>

<style scoped>
.sec-header {
  @apply flex items-center justify-between w-full text-[11px] font-semibold text-gray-400 uppercase tracking-wider outline-none cursor-pointer;
}
.slider-label {
  @apply text-[12px] text-gray-600 flex justify-between mb-1.5;
}
.slider {
  @apply w-full h-1.5 accent-orange-500 rounded-full appearance-none bg-gray-200 cursor-pointer;
}
</style>
