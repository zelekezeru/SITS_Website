<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import InputError from '@/Components/Library/InputError.vue';
import InputLabel from '@/Components/Library/InputLabel.vue';
import TextInput from '@/Components/Library/TextInput.vue';
import PrimaryButton from '@/Components/Library/PrimaryButton.vue';

const props = defineProps({
    campus: Object,
});

const form = useForm({
    name: props.campus.name,
    code: props.campus.code,
    address: props.campus.address,
    is_active: Boolean(props.campus.is_active),
});

const submit = () => {
    form.patch(route('library.campuses.update', props.campus.id));
};

const deleteCampus = () => {
    if (confirm('Are you sure you want to archive this campus? All associated floors and rows will also be archived.')) {
        form.delete(route('library.campuses.destroy', props.campus.id));
    }
};
</script>

<template>
    <Head title="Edit Campus" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-black text-2xl text-gray-900 dark:text-white leading-tight tracking-tight uppercase">
                    Configure Campus
                </h2>
                <button @click="deleteCampus" class="px-6 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-red-100 dark:hover:bg-red-900/40 transition-all active:scale-95">
                    Archive Entity
                </button>
            </div>
        </template>

        <div class="py-10 px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-2xl shadow-gray-200/50 dark:shadow-none rounded-[2.5rem] border border-transparent dark:border-gray-800 transition-all">
                    <div class="p-8">
                        <div class="mb-8 pb-8 border-b dark:border-gray-800">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white leading-tight mb-1">{{ campus.name }}</h3>
                            <p class="text-xs text-gray-400 font-mono">UUID: {{ campus.id }}</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <div>
                                <InputLabel for="name" value="Campus Name" class="text-[10px] font-black uppercase tracking-[0.2em] mb-2 text-gray-400" />
                                <TextInput id="name" type="text" class="mt-1 block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 focus:ring-2 focus:ring-indigo-500 transition-all" v-model="form.name" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="code" value="Campus Code" class="text-[10px] font-black uppercase tracking-[0.2em] mb-2 text-gray-400" />
                                <TextInput id="code" type="text" class="mt-1 block w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 font-mono focus:ring-2 focus:ring-indigo-500 transition-all" v-model="form.code" required />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div>
                                <InputLabel for="address" value="Geographic Address" class="text-[10px] font-black uppercase tracking-[0.2em] mb-2 text-gray-400" />
                                <textarea 
                                    id="address" 
                                    v-model="form.address" 
                                    rows="4" 
                                    class="mt-1 block w-full border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 dark:text-gray-300 rounded-2xl shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-all"
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-800 transition-colors">
                                <input id="is_active" type="checkbox" v-model="form.is_active" class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 transition-all" />
                                <label for="is_active" class="ml-4 text-sm font-bold text-gray-700 dark:text-gray-300">This entity is currently operational</label>
                            </div>

                            <div class="flex items-center justify-end mt-8 pt-8 border-t dark:border-gray-800">
                                <Link :href="route('library.campuses.index')" class="text-xs font-black uppercase tracking-widest text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-8 transition-colors">
                                    Cancel
                                </Link>
                                <PrimaryButton 
                                    class="px-8 py-3.5 bg-indigo-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 shadow-xl shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-25" 
                                    :disabled="form.processing"
                                >
                                    Update Configuration
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
