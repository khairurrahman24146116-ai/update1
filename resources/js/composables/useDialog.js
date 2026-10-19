import { reactive } from 'vue'

const state = reactive({
  open: false,
  type: 'alert',
  variant: 'info',
  title: '',
  message: '',
  inputPlaceholder: '',
  inputValue: '',
  confirmText: 'OK',
  cancelText: 'Batal',
  _resolve: null,
})

function open(options) {
  state.open = true
  state.type = options.type || 'alert'
  state.variant = options.variant || 'info'
  state.title = options.title || ''
  state.message = options.message || ''
  state.inputPlaceholder = options.inputPlaceholder || ''
  state.inputValue = options.inputValue ?? ''
  state.confirmText = options.confirmText || 'OK'
  state.cancelText = options.cancelText || 'Batal'

  return new Promise((resolve) => {
    state._resolve = resolve
  })
}

function settle(value) {
  state.open = false
  const resolve = state._resolve
  state._resolve = null
  if (resolve) resolve(value)
}

export function useDialog() {
  return {
    state,
    alert: (message, options = {}) => open({ type: 'alert', variant: options.variant || 'info', ...options, message }),
    confirm: (message, options = {}) => open({ type: 'confirm', variant: options.variant || 'warning', confirmText: 'Ya', cancelText: 'Batal', ...options, message }),
    prompt: (message, options = {}) => open({ type: 'prompt', variant: options.variant || 'info', confirmText: 'Simpan', cancelText: 'Batal', ...options, message }),
    accept: () => settle(state.type === 'confirm' ? true : state.type === 'prompt' ? state.inputValue : true),
    cancel: () => settle(state.type === 'confirm' ? false : null),
  }
}
