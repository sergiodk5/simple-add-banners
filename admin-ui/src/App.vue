<script setup lang="ts">
	import BannerForm from '@/components/BannerForm.vue'
	import BannerList from '@/components/BannerList.vue'
	import PlacementForm from '@/components/PlacementForm.vue'
	import PlacementList from '@/components/PlacementList.vue'
	import type { Banner } from '@/types/banner'
	import type { Placement } from '@/types/placement'
	import TabMenu from 'primevue/tabmenu'
	import Toast from 'primevue/toast'
	import { ref } from 'vue'

	const bannerListRef = ref<InstanceType<typeof BannerList> | null>(null)
	const placementListRef = ref<InstanceType<typeof PlacementList> | null>(null)

	const activeTab = ref(0)
	const tabItems = [
		{ label: 'Banners', icon: 'pi pi-images' },
		{ label: 'Placements', icon: 'pi pi-th-large' },
	]

	// Banner state
	const bannerFormVisible = ref(false)
	const editingBanner = ref<Banner | null>(null)

	const handleCreateBanner = () => {
		editingBanner.value = null
		bannerFormVisible.value = true
	}

	const handleEditBanner = (banner: Banner) => {
		editingBanner.value = banner
		bannerFormVisible.value = true
	}

	const handleBannerSaved = () => {
		bannerListRef.value?.loadBanners()
	}

	// Placement state
	const placementFormVisible = ref(false)
	const editingPlacement = ref<Placement | null>(null)

	const handleCreatePlacement = () => {
		editingPlacement.value = null
		placementFormVisible.value = true
	}

	const handleEditPlacement = (placement: Placement) => {
		editingPlacement.value = placement
		placementFormVisible.value = true
	}

	const handlePlacementSaved = () => {
		placementListRef.value?.loadPlacements()
	}
</script>

<template>
  <div class="tw:p-4">
    <Toast />

    <TabMenu
      v-model:active-index="activeTab"
      :model="tabItems"
      class="tw:mb-4"
    />

    <div v-show="activeTab === 0">
      <BannerList
        ref="bannerListRef"
        @create="handleCreateBanner"
        @edit="handleEditBanner"
      />

      <BannerForm
        v-model:visible="bannerFormVisible"
        :banner="editingBanner"
        @saved="handleBannerSaved"
      />
    </div>

    <div v-show="activeTab === 1">
      <PlacementList
        ref="placementListRef"
        @create="handleCreatePlacement"
        @edit="handleEditPlacement"
      />

      <PlacementForm
        v-model:visible="placementFormVisible"
        :placement="editingPlacement"
        @saved="handlePlacementSaved"
      />
    </div>
  </div>
</template>
