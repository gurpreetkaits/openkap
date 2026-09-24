<template>
  <div class="p-4 space-y-5">

    <!-- ─── Background ─── -->
    <section class="bg-gray-50 rounded-xl p-3.5">
      <button @click="bgOpen = !bgOpen" class="sec-header">
        <span>Background</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="bgOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="accordion-body" :class="bgOpen ? 'accordion-open' : ''"><div class="space-y-3 mt-2">
        <!-- Type pills -->
        <div class="flex p-0.5 bg-gray-100 rounded-lg">
          <button v-for="t in ['none','solid','gradient','image']" :key="t" @click="styleBackground.type = t"
            :class="styleBackground.type === t ? 'bg-white shadow-xs text-gray-900' : 'text-gray-500'"
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
            <Input type="text" v-model="styleBackground.color" class="flex-1 font-mono" />
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
            <select v-model="styleBackground.gradientDirection" class="flex-1 text-[11px] px-2 py-1.5 bg-gray-50 rounded-lg border border-gray-200 outline-hidden text-gray-700">
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
      </div></div>
    </section>

    <!-- ─── Video Frame ─── -->
    <section class="bg-gray-50 rounded-xl p-3.5">
      <button @click="frameOpen = !frameOpen" class="sec-header">
        <span>Video Frame</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="frameOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="accordion-body" :class="frameOpen ? 'accordion-open' : ''"><div class="space-y-3 mt-2">
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
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-4 transition-transform"></div>
          </div>
        </label>
      </div></div>
    </section>

    <!-- ─── Zoom ─── -->
    <section class="bg-gray-50 rounded-xl p-3.5">
      <button @click="zoomOpen = !zoomOpen" class="sec-header">
        <span>Zoom</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="zoomOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="accordion-body" :class="zoomOpen ? 'accordion-open' : ''"><div class="space-y-3 mt-2">

        <!-- Placement mode banner -->
        <div v-if="zoomPlacementMode" class="flex items-center gap-2 px-3 py-2.5 bg-orange-50 border border-orange-200 rounded-lg">
          <svg class="w-4 h-4 text-orange-500 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
          <span class="text-[11px] text-orange-700 font-medium flex-1">Click on the video to set zoom focus point</span>
          <Button variant="ghost" @click="zoomPlacementMode = false">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </Button>
        </div>

        <!-- Add zoom button -->
        <Button size="sm"
          v-if="!zoomPlacementMode"
          @click="handleAddZoom"
          class="w-full flex items-center justify-center gap-2">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
          Add Zoom
        </Button>

        <!-- Keyframe list -->
        <div v-if="zoomKeyframes.length" class="space-y-1.5">
          <div
            v-for="kf in zoomKeyframes" :key="kf.id"
            @click="selectZoom(kf)"
            class="p-2.5 bg-white rounded-lg border transition-all cursor-pointer group"
            :class="selectedZoomId === kf.id ? 'border-orange-400 ring-2 ring-orange-100' : 'border-gray-100 hover:border-gray-200'"
          >
            <!-- Header row: time + delete -->
            <div class="flex items-center gap-2 mb-2">
              <Button variant="ghost" @click.stop="seekTo(kf.time)" class="font-mono">
                {{ formatTime(kf.time) }}
              </Button>
              <span class="text-[10px] text-gray-300">-</span>
              <span class="text-[11px] font-mono text-gray-400">{{ formatTime(kf.time + kf.duration) }}</span>
              <div class="flex-1"></div>
              <Button variant="destructive" @click.stop="removeZoomKeyframe(kf.id)" class="opacity-0 group-hover:opacity-100">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </Button>
            </div>

            <!-- Controls (shown when selected) -->
            <div v-if="selectedZoomId === kf.id" class="space-y-2">
              <div>
                <label class="slider-label"><span>Zoom Level</span><span class="font-mono text-gray-500">{{ kf.scale.toFixed(1) }}x</span></label>
                <input
                  type="range" :value="kf.scale" @input="updateZoomKeyframe(kf.id, { scale: parseFloat($event.target.value) })"
                  min="1.2" max="4" step="0.1" class="slider"
                />
              </div>
              <div>
                <label class="slider-label"><span>Duration</span><span class="font-mono text-gray-500">{{ kf.duration.toFixed(1) }}s</span></label>
                <input
                  type="range" :value="kf.duration" @input="updateZoomKeyframe(kf.id, { duration: parseFloat($event.target.value) })"
                  min="0.5" max="10" step="0.1" class="slider"
                />
              </div>
              <Button variant="ghost" size="sm"
                @click.stop="startRepositionZoom(kf.id)"
                class="w-full flex items-center justify-center gap-1.5">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                Reposition Focus Point
              </Button>
            </div>
          </div>
        </div>

        <p v-if="!zoomKeyframes.length && !zoomPlacementMode" class="text-[10px] text-gray-400 leading-relaxed">Add zoom points to focus on areas of your screen during playback. Works like Tella and Screen Studio.</p>
      </div></div>
    </section>

    <!-- ─── Camera ─── -->
    <section v-if="video?.has_camera" class="bg-gray-50 rounded-xl p-3.5">
      <button @click="camOpen = !camOpen" class="sec-header">
        <span>Camera</span>
        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="camOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>
      <div class="accordion-body" :class="camOpen ? 'accordion-open' : ''"><div class="space-y-3 mt-2">
        <label class="flex items-center justify-between cursor-pointer py-1">
          <span class="text-xs text-gray-600 font-medium">Show Camera</span>
          <div class="relative">
            <input type="checkbox" v-model="cameraEnabled" class="sr-only peer" />
            <div class="w-9 h-5 bg-gray-200 peer-checked:bg-orange-500 rounded-full transition-colors"></div>
            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-4 transition-transform"></div>
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
                :class="cameraShape === s ? 'bg-white shadow-xs text-gray-900' : 'text-gray-500'"
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

          <div>
            <label class="slider-label"><span>Border Blur</span><span class="font-mono text-gray-500">{{ cameraBorderBlur }}px</span></label>
            <input type="range" v-model.number="cameraBorderBlur" min="0" max="20" step="1" class="slider" />
          </div>

          <div>
            <label class="slider-label"><span>Shadow</span><span class="font-mono text-gray-500">{{ cameraShadow }}%</span></label>
            <input type="range" v-model.number="cameraShadow" min="0" max="60" step="1" class="slider" />
          </div>
        </template>
      </div></div>
    </section>
  </div>
</template>

<script setup>
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { ref } from 'vue'
import { useEditorState } from '@/composables/useEditorState'

const {
  video, videoEl, currentTime,
  styleBackground, stylePadding, styleRoundness, styleShadow,
  cameraEnabled, cameraPosition, cameraSize, cameraRoundness, cameraShape,
  cameraBorderBlur, cameraShadow,
  bgPresetColors, bgPresetGradients, bgPresetImages,
  zoomKeyframes, zoomPlacementMode, selectedZoomId,
  addZoomKeyframe, updateZoomKeyframe, removeZoomKeyframe,
  formatTime,
} = useEditorState()

const bgOpen = ref(true)
const frameOpen = ref(true)
const camOpen = ref(true)
const zoomOpen = ref(true)

function handleAddZoom() {
  selectedZoomId.value = null
  zoomPlacementMode.value = true
}

function startRepositionZoom(id) {
  selectedZoomId.value = id
  zoomPlacementMode.value = true
}

function selectZoom(kf) {
  selectedZoomId.value = selectedZoomId.value === kf.id ? null : kf.id
}

function seekTo(time) {
  const el = videoEl.value
  if (el) el.currentTime = time
}

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
/* Tailwind v4 compiles each SFC style block on its own, so @apply
   below needs an explicit reference to the theme. */
@reference "../../style.css";

.sec-header {
  @apply flex items-center justify-between w-full text-[11px] font-semibold text-gray-400 uppercase tracking-wider outline-hidden cursor-pointer;
}
.slider-label {
  @apply text-[12px] text-gray-600 flex justify-between mb-1.5;
}
.slider {
  @apply w-full h-1.5 accent-orange-500 rounded-full appearance-none bg-gray-200 cursor-pointer;
}
.accordion-body {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.25s ease;
  overflow: hidden;
}
.accordion-body > div {
  overflow: hidden;
  min-height: 0;
}
.accordion-body.accordion-open {
  grid-template-rows: 1fr;
}
</style>
