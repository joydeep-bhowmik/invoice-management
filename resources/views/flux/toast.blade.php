<!-- Toast Component HTML -->
<div x-data="{
    // Default configuration
    config: {
        duration: 5000,
        variant: 'default',
        heading: '',
        text: '',
        position: 'bottom end',
        closable: true,
        actions: [] // New: Array of action objects
    },

    // Toast visibility state
    visible: false,
    timeoutId: null,

    // Initialize the component
    init() {
        // Listen for toast show events
        document.addEventListener('toast-show', (event) => {
            this.showToast(event.detail);
        });

        // Set up click handler for close button
        this.$watch('visible', (value) => {
            if (value && this.config.duration > 0) {
                this.autoDismiss();
            }
        });
    },

    // Show toast with given options (supports Flux shape)
    showToast(options) {
        this.config = {
            duration: 5000,
            variant: 'default',
            heading: '',
            text: '',
            position: 'bottom end',
            closable: true,
            actions: []
        };

        // Flux dispatches detail with { slots: { heading, text }, dataset: { variant, position }, duration }
        const detail = options || {};
        const slots = detail.slots || {};
        const dataset = detail.dataset || {};

        const normalized = {
            heading: slots.heading ?? detail.heading ?? this.config.heading,
            text: slots.text ?? detail.text ?? this.config.text,
            variant: dataset.variant ?? detail.variant ?? this.config.variant,
            position: dataset.position ?? detail.position ?? this.config.position,
            duration: detail.duration !== undefined ? detail.duration : this.config.duration,
            closable: detail.closable !== undefined ? detail.closable : this.config.closable,
            actions: detail.actions ?? this.config.actions // Handle actions
        };

        // Merge with default config
        this.config = { ...this.config, ...normalized };

        // Update variant attribute
        this.$el.setAttribute('data-variant', this.config.variant);

        // Update position
        this.updatePosition();

        // Show the toast
        this.visible = true;

        // If duration is 0 (indefinite), don't auto dismiss
        if (this.config.duration === 0) {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId);
                this.timeoutId = null;
            }
        }
    },

    // Update toast position
    updatePosition() {
        const positions = {
            'top start': 'top-4 left-4',
            'top center': 'top-4 left-1/2 -translate-x-1/2',
            'top end': 'top-4 right-4',
            'bottom start': 'bottom-4 left-4',
            'bottom center': 'bottom-4 left-1/2 -translate-x-1/2',
            'bottom end': 'bottom-4 left-0 right-0 md:left-auto md:right-4',
            'middle start': 'top-1/2 -translate-y-1/2 left-4',
            'middle center': 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
            'middle end': 'top-1/2 -translate-y-1/2 right-4'
        };

        const positionClass = positions[this.config.position] || positions['bottom end'];

        // Remove existing position classes
        const positionClasses = Object.values(positions).join(' ');
        this.$el.classList.remove(...positionClasses.split(' '));

        // Add new position class
        this.$el.classList.add(...positionClass.split(' '));
    },

    // Auto dismiss after duration
    autoDismiss() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }

        this.timeoutId = setTimeout(() => {
            this.dismiss();
        }, this.config.duration);
    },

    // Dismiss the toast
    dismiss() {
        this.visible = false;
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    },

    // Handle action click
    handleAction(action) {
        // Close toast when action is clicked (optional)
        if (action.dismiss !== false) {
            this.dismiss();
        }

        // If action has a callback function, execute it
        if (typeof action.callback === 'function') {
            action.callback();
        }
    }
}" wire:ignore="" role="status" aria-live="assertive" aria-atomic="true" x-show="visible"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed z-50"
    :data-variant="config.variant" role="alert" aria-live="assertive" aria-atomic="true">

    <div class="md:max-w-sm  w-full mr-3 px-2 ">
        <div
            class="p-2 flex rounded-xl shadow-lg bg-white border border-zinc-200 border-b-zinc-300/80 dark:bg-zinc-700 dark:border-zinc-600">
            <div class="flex-1 flex items-start gap-4 overflow-hidden">
                <div class="flex-1 py-1.5 ps-2.5">


                    <div class="flex items-start gap-2">
                        <div class="mt-0.5">
                            <!-- Dynamic Icon based on variant -->
                            <template x-if="config.variant == 'success'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                    class="shrink-0 size-4 text-lime-600 dark:text-lime-400">
                                    <path fill-rule="evenodd"
                                        d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.15-.043l4.25-5.5Z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </template>

                            <template x-if="config.variant == 'warning'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                    class="shrink-0 size-4 text-amber-500 dark:text-amber-400">
                                    <path fill-rule="evenodd"
                                        d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 1 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </template>

                            <template x-if="config.variant == 'danger'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                    class="shrink-0 size-4 text-rose-500 dark:text-rose-400">
                                    <path fill-rule="evenodd"
                                        d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </template>
                        </div>

                        <div class="flex-1">
                            <!-- Heading Slot -->
                            <div x-show="config.heading" class="font-medium text-sm text-zinc-800 dark:text-white pb-1">
                                <span x-text="config.heading"></span>
                            </div>

                            <!-- Text Content -->
                            <div class="text-sm text-zinc-600 dark:text-zinc-300" x-text="config.text"></div>

                            <!-- Actions Container -->
                            <div x-show="config.actions && config.actions.length > 0" class="mt-3 flex gap-2">
                                <template x-for="action in config.actions">
                                    <div>
                                        <!-- Link Action -->
                                        <template x-if="action.type === 'link' || action.href">
                                            <a :href="action.href" :target="action.target || '_self'"
                                                @click="handleAction(action)"
                                                class="inline-flex items-center  py-1.5 text-xs font-medium rounded-md transition-colors"
                                                :class="{
                                                    'bg-primary-100 text-primary-700 hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300 dark:hover:bg-primary-800': action
                                                        .variant === 'primary' || !action.variant,
                                                    'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700': action
                                                        .variant === 'secondary',
                                                    'bg-transparent text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/50': action
                                                        .variant === 'text'
                                                }"
                                                x-text="action.label" x-navigate>
                                            </a>
                                        </template>

                                        <!-- Button Action (for wire:click or event dispatch) -->
                                        <template x-if="action.type === 'button' || !action.href">
                                            <button type="button" @click="handleAction(action)"
                                                :wire:click="action.wireClick"
                                                class="inline-flex items-center  py-1.5 text-xs font-medium rounded-md transition-colors"
                                                x-bind:class="{
                                                    'bg-primary-100 text-primary-700 hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300 dark:hover:bg-primary-800': action
                                                        .variant === 'primary' || !action.variant,
                                                    'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700': action
                                                        .variant === 'secondary',
                                                    'bg-transparent text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/50': action
                                                        .variant === 'text'
                                                }"
                                                x-text="action.label">
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Close Button -->
                <button @click="dismiss()" type="button"
                    class="inline-flex items-center font-medium justify-center gap-2 truncate h-8 text-sm rounded-md w-8 bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-400 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white"
                    aria-label="Close notification">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
