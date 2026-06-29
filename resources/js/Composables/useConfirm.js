import { ref } from 'vue';

const confirmState = ref({
  isOpen: false,
  title: '',
  message: '',
  onConfirm: null,
  onCancel: null,
});

export function useConfirm() {
  const confirm = (options = {}) => {
    return new Promise((resolve) => {
      confirmState.value = {
        isOpen: true,
        title: options.title || 'Are you sure?',
        message: options.message || 'Do you really want to proceed with this action?',
        onConfirm: () => {
          confirmState.value.isOpen = false;
          resolve(true);
        },
        onCancel: () => {
          confirmState.value.isOpen = false;
          resolve(false);
        },
      };
    });
  };

  return {
    confirmState,
    confirm,
  };
}
