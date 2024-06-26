<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const campaignName = ref('');
const csvFile = ref(null);
const campaignProgress = ref(null);

const form = useForm({
    name: '',
    csv_file: null,
});

const handleFileUpload = (event) => {
    csvFile.value = event.target.files[0];
    form.csv_file = csvFile.value;
};

const createCampaign = async () => {
    if (!form.csv_file) {
        alert('Please upload a CSV file');
        return;
    }

    form.post('/campaign/store', {
        onSuccess: () => {
            alert('Campaign created successfully');
            form.reset();
            campaignName.value = '';
            csvFile.value = null;
            fetchCampaignProgress();
        },
        onError: () => {
            alert('Error creating campaign');
        },
    });
};

const fetchCampaignProgress = async () => {
    try {
        const response = await axios.get('/campaign/progress');
        campaignProgress.value = response.data;
    } catch (error) {
        console.error('Error fetching campaign progress:', error);
    }
};

onMounted(() => {
    fetchCampaignProgress();
});
</script>

<template>
    <Head title="Create Campaign" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Create Campaign</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <div class="campaign-container">
                        <form @submit.prevent="createCampaign">
                            <div class="form-group">
                                <InputLabel for="campaignName" value="Campaign Name" />
                                <TextInput
                                    id="campaignName"
                                    v-model="form.name"
                                    required
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.name" class="mt-5" />
                            </div>
                            <div class="form-group mt-3">
                                <InputLabel for="csvFile" value="Upload CSV" />
                                <input
                                    type="file"
                                    id="csvFile"
                                    @change="handleFileUpload"
                                    required
                                    class="form-control mt-2"
                                    ref="csvFileInput"
                                />
                                <InputError :message="form.errors.csv_file" class="mt-2" />
                            </div>
                            <PrimaryButton :disabled="form.processing" class="mt-5">Submit</PrimaryButton>
                        </form>
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Campaign Progress</h3>
                    <div v-if="campaignProgress">
                        <p>Processed Contacts: {{ campaignProgress.processed_contacts }}</p>
                        <p>Total Contacts: {{ campaignProgress.total_contacts }}</p>
                        <p>Status: {{ campaignProgress.status }}</p>
                    </div>
                    <div v-else>
                        <p>Loading campaign progress...</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.campaign-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
}
</style>
