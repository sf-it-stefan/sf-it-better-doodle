{{-- Rendered once by Blade inside a <template x-for>; Alpine clones it per field. --}}
<div x-data="dateSlotBuilder(field)" x-init="init()">

    <div class="flex items-center justify-between mb-2">
        <label class="block text-xs text-white/50">Date/Time Slots</label>
        <button type="button" @click="pasteMode = !pasteMode"
                class="text-xs text-white/40 hover:text-white/70"
                x-text="pasteMode ? 'Use calendar' : 'Paste as text'"></button>
    </div>

    {{-- ---------- Calendar + pattern ---------- --}}
    <div x-show="!pasteMode" class="space-y-3">

        <div class="bg-surface rounded-lg p-3">
            <div class="flex items-center justify-between mb-2">
                <button type="button" @click="shiftMonth(-1)"
                        class="px-2 py-1 rounded text-white/50 hover:text-white hover:bg-surface-light">&larr;</button>
                <span class="text-xs font-medium text-white/70" x-text="monthLabel"></span>
                <button type="button" @click="shiftMonth(1)"
                        class="px-2 py-1 rounded text-white/50 hover:text-white hover:bg-surface-light">&rarr;</button>
            </div>

            <div class="grid grid-cols-7 gap-1 mb-1">
                <template x-for="d in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="d">
                    <div class="text-center text-[10px] text-white/30 uppercase" x-text="d"></div>
                </template>
            </div>

            <div class="grid grid-cols-7 gap-1" role="grid"
                 @mouseleave="dragFrom = null"
                 @mouseup.window="dragFrom = null">
                <template x-for="cell in calendarCells" :key="cell.date">
                    <button type="button"
                            role="gridcell"
                            :aria-selected="isSelected(cell.date)"
                            :tabindex="cell.inMonth ? 0 : -1"
                            @mousedown.prevent="dragFrom = cell.date; toggleDay(cell.date)"
                            @mouseenter="if (dragFrom) selectRange(dragFrom, cell.date)"
                            @keydown.enter.prevent="toggleDay(cell.date)"
                            @keydown.space.prevent="toggleDay(cell.date)"
                            class="h-9 rounded text-xs transition-colors select-none"
                            :class="[
                                cell.inMonth ? '' : 'opacity-30',
                                isSelected(cell.date)
                                    ? 'bg-brand-500 text-black font-semibold'
                                    : (hasSlots(cell.date)
                                        ? 'bg-brand-500/20 text-brand-200 hover:bg-brand-500/30'
                                        : 'text-white/60 hover:bg-surface-light')
                            ]"
                            x-text="cell.day"></button>
                </template>
            </div>

            <p class="text-[10px] text-white/25 mt-2">Click to pick days, drag to select a range.</p>
        </div>

        <div x-show="days.length" class="bg-surface rounded-lg p-3 space-y-3">
            <div class="flex flex-wrap items-end gap-2">
                <label class="flex flex-col gap-1">
                    <span class="text-[10px] text-white/40 uppercase tracking-wider">Start</span>
                    <input type="time" step="900" x-model="pattern.start"
                           class="rounded-lg border-0 py-1.5 px-2 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[10px] text-white/40 uppercase tracking-wider">Length</span>
                    <input type="number" min="5" step="5" x-model.number="pattern.duration"
                           class="w-20 rounded-lg border-0 py-1.5 px-2 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[10px] text-white/40 uppercase tracking-wider">Slots</span>
                    <input type="number" min="1" max="24" x-model.number="pattern.repeat"
                           class="w-16 rounded-lg border-0 py-1.5 px-2 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-[10px] text-white/40 uppercase tracking-wider">Gap</span>
                    <input type="number" min="0" step="5" x-model.number="pattern.gap"
                           class="w-16 rounded-lg border-0 py-1.5 px-2 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                </label>
            </div>

            <p class="text-xs text-white/40">
                <span x-text="previewSlots.map(s => s.start_time + '–' + s.end_time).join(', ')"></span>
            </p>

            <div class="flex items-center gap-2">
                <button type="button" @click="applyPattern()"
                        class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-black hover:bg-brand-400 transition-colors"
                        x-text="`Apply to ${days.length} selected ${days.length === 1 ? 'day' : 'days'}`"></button>
                <button type="button" @click="days = []" class="text-xs text-white/40 hover:text-white/70">Deselect days</button>
            </div>
        </div>
    </div>

    {{-- ---------- Paste ---------- --}}
    <div x-show="pasteMode" x-cloak class="space-y-2">
        <textarea x-model="pasteText" rows="5" spellcheck="false"
                  placeholder="2026-08-24 13:00-14:00, 14:00-15:00&#10;2026-08-26 13:00-17:00 /60"
                  class="w-full rounded-lg border-0 py-2 px-3 bg-surface text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm font-mono placeholder:text-white/20"></textarea>
        <p class="text-[10px] text-white/30">One day per line. <code>/60</code> splits a range into 60-minute slots.</p>
        <div class="flex items-center gap-2">
            <button type="button" @click="applyPaste(false)"
                    class="rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-medium text-black hover:bg-brand-400 transition-colors">Add slots</button>
            <button type="button" @click="applyPaste(true)"
                    class="text-xs text-white/40 hover:text-white/70">Replace all</button>
        </div>
        <template x-for="err in pasteErrors" :key="err">
            <p class="text-xs text-red-400" x-text="err"></p>
        </template>
    </div>

    {{-- ---------- Generated slots ---------- --}}
    <div class="mt-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs text-white/50">
                <span x-text="slots.length"></span> slot<span x-show="slots.length !== 1">s</span>
            </p>
            <button type="button" x-show="slots.length" @click="field.options.slots = []"
                    class="text-xs text-red-400/50 hover:text-red-400">Clear all</button>
        </div>

        <p x-show="!slots.length" class="text-xs text-white/25 italic">No slots yet.</p>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <template x-for="group in groupedSlots" :key="group.date">
                <div class="bg-surface rounded-lg p-2 self-start">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <span class="text-[10px] uppercase tracking-wider text-white/50" x-text="formatDay(group.date)"></span>
                        <button type="button" @click="removeDay(group.date)"
                                class="text-red-400/40 hover:text-red-400 text-xs">Remove day</button>
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="slot in group.slots" :key="slot._id">
                            {{-- min-w-0 lets the time inputs shrink; without it they
                                 keep their intrinsic width and push the × out of the card. --}}
                            <div class="flex gap-1.5 items-center">
                                <input type="time" step="900" x-model="slot.start_time"
                                       class="flex-1 min-w-0 rounded-lg border-0 py-1 px-2 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                <span class="text-white/30 shrink-0">&ndash;</span>
                                <input type="time" step="900" x-model="slot.end_time"
                                       class="flex-1 min-w-0 rounded-lg border-0 py-1 px-2 bg-surface-light text-gray-100 ring-1 ring-inset ring-surface-lighter focus:ring-2 focus:ring-brand-500 text-sm">
                                <button type="button" @click="removeSlot(slot)"
                                        class="shrink-0 text-red-400/50 hover:text-red-400 px-1 text-sm">&times;</button>
                            </div>
                        </template>
                        <button type="button" @click="addSlotTo(group.date)"
                                class="text-xs text-brand-400/70 hover:text-brand-300">+ slot</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="mt-3">
        <label class="flex items-center gap-2 text-xs text-white/50">
            <input type="checkbox" x-model="field.options.multi_select"
                   class="rounded border-surface-lighter bg-surface text-brand-500 focus:ring-brand-500 h-3.5 w-3.5">
            Allow selecting multiple slots
        </label>
    </div>
</div>
