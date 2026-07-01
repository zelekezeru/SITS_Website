<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';

const props = defineProps({
    campus: Object,
    floors: Array,
});

const { can } = useCan();

// Toggle state
const expandedFloors = ref(new Set());
const expandedRows = ref(new Set());

const toggleFloor = (id) => {
    if (expandedFloors.value.has(id)) expandedFloors.value.delete(id);
    else expandedFloors.value.add(id);
};

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) expandedRows.value.delete(id);
    else expandedRows.value.add(id);
};

// Forms
const floorForm = useForm({ name: '', level: 0 });
const rowForm = useForm({ label: '', subject_area: '' });
const shelfBoxForm = useForm({ label: '', capacity: 30 });

const addingFloor = ref(false);
const addingRowTo = ref(null);
const addingShelfBoxTo = ref(null);

const addFloor = () => {
    floorForm.post(route('library.campuses.floors.store', props.campus.id), {
        preserveScroll: true,
        onSuccess: () => { addingFloor.value = false; floorForm.reset(); }
    });
};

const addRow = (floorId) => {
    rowForm.post(route('library.floors.rows.store', floorId), {
        preserveScroll: true,
        onSuccess: () => { addingRowTo.value = null; rowForm.reset(); expandedFloors.value.add(floorId); }
    });
};

const addShelfBox = (rowId) => {
    shelfBoxForm.post(route('library.rows.shelf-boxes.store', rowId), {
        preserveScroll: true,
        onSuccess: () => { addingShelfBoxTo.value = null; shelfBoxForm.reset(); expandedRows.value.add(rowId); }
    });
};
</script>

<template>
    <Head title="Spatial Tree" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ campus.name }} ({{ campus.code }}) Spatial Tree
                </h2>
                <!-- Future: Campus Dropdown here if multiple campuses -->
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg p-6 border border-transparent dark:border-gray-800">
                    
                    <div class="mb-4">
                        <button v-if="can('manage_floor')" @click="addingFloor = !addingFloor" class="text-sm bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-3 py-1 rounded hover:bg-indigo-100 dark:hover:bg-indigo-900 transition">
                            + Add Floor
                        </button>
                    </div>

                    <div v-if="addingFloor" class="mb-4 p-4 border dark:border-gray-800 rounded bg-gray-50 dark:bg-gray-800 flex gap-2">
                        <input v-model="floorForm.name" type="text" placeholder="Floor Name (e.g. Ground)" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm" />
                        <input v-model="floorForm.level" type="number" placeholder="Level" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm w-24" />
                        <button @click="addFloor" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
                        <button @click="addingFloor = false" class="text-gray-500 dark:text-gray-400 text-sm px-2">Cancel</button>
                    </div>

                    <ul class="space-y-2">
                        <li v-for="floor in campus.floors" :key="floor.id" class="border dark:border-gray-800 rounded-md">
                            <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-800/50 p-3 rounded-t-md cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition" @click="toggleFloor(floor.id)">
                                <div class="flex items-center space-x-2">
                                    <span class="text-gray-500 dark:text-gray-400 w-4 inline-block text-center">{{ expandedFloors.has(floor.id) ? '▾' : '▸' }}</span>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">Floor: {{ floor.name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-500">(Level {{ floor.level }})</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button v-if="can('manage_row')" @click.stop="addingRowTo = floor.id" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">+ Add Row</button>
                                </div>
                            </div>

                            <div v-show="expandedFloors.has(floor.id)" class="p-3 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 pl-8">
                                
                                <div v-if="addingRowTo === floor.id" class="mb-3 p-3 bg-gray-50 dark:bg-gray-800 border dark:border-gray-700 rounded-md flex gap-2">
                                    <input v-model="rowForm.label" type="text" placeholder="Row Label (e.g. A)" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm" />
                                    <input v-model="rowForm.subject_area" type="text" placeholder="Subject (Optional)" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-sm" />
                                    <button @click="addRow(floor.id)" class="bg-indigo-600 text-white px-3 py-1 rounded-md text-sm hover:bg-indigo-700">Save Row</button>
                                    <button @click="addingRowTo = null" class="text-gray-500 dark:text-gray-400 text-sm px-2">Cancel</button>
                                </div>

                                <ul class="space-y-2">
                                    <li v-for="row in floor.rows" :key="row.id" class="border rounded-md border-gray-200 dark:border-gray-800">
                                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/30 p-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800/50 transition" @click="toggleRow(row.id)">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-gray-400 dark:text-gray-500 w-4 inline-block text-center">{{ expandedRows.has(row.id) ? '▾' : '▸' }}</span>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">Row: {{ row.label }}</span>
                                                <span v-if="row.subject_area" class="text-xs text-gray-500 dark:text-gray-500 bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ row.subject_area }}</span>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <button v-if="can('manage_shelf_box')" @click.stop="addingShelfBoxTo = row.id" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-medium">+ Add Shelf Box</button>
                                            </div>
                                        </div>

                                        <div v-show="expandedRows.has(row.id)" class="p-2 bg-white dark:bg-gray-900 pl-8 space-y-2 border-t border-gray-100 dark:border-gray-800">
                                            
                                            <div v-if="addingShelfBoxTo === row.id" class="mb-2 p-2 bg-gray-50 dark:bg-gray-800 border dark:border-gray-700 rounded flex gap-2 items-center">
                                                <input v-model="shelfBoxForm.label" type="text" placeholder="Label (e.g. A-01)" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded shadow-sm text-xs w-32" />
                                                <input v-model="shelfBoxForm.capacity" type="number" placeholder="Capacity" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded shadow-sm text-xs w-24" />
                                                <button @click="addShelfBox(row.id)" class="bg-indigo-600 text-white px-2 py-1 rounded text-xs hover:bg-indigo-700">Save</button>
                                                <button @click="addingShelfBoxTo = null" class="text-gray-500 dark:text-gray-400 text-xs px-2">Cancel</button>
                                            </div>

                                            <div v-if="!row.shelf_boxes || row.shelf_boxes.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic py-1">No shelf boxes in this row.</div>
                                            
                                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                                <div v-for="box in row.shelf_boxes" :key="box.id" class="flex items-center justify-between p-2 border rounded border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800/50 hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ box.label }}</span>
                                                        <span class="text-xs text-gray-500 dark:text-gray-500" v-if="box.capacity">(Max {{ box.capacity }})</span>
                                                    </div>
                                                    <div class="flex space-x-1">
                                                        <a :href="route('library.shelf-boxes.qr', box.id)" target="_blank" class="text-gray-400 dark:text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="Print QR">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    </ul>
                    
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
