<script setup>
import { auth } from "@/utils/auth";
import getImageUrl from "@/utils/image/getImageUrl";
import { computed, ref, watch } from "vue";
import moment from "moment-timezone";
import { useAuthStore } from "@/stores/authStore";
import { api } from "@/utils/api";

const isLoading = ref(false);
const formData = ref({ ...auth().user });

const user = auth().user;

const sections = [
  {
    title: "Personal Information",
    icon: "tabler-id",
    items: [
      { title: "Full Name", value: user.name_kh, icon: "tabler-user" },
      { title: "Username", value: user.username, icon: "tabler-at" },
      { title: "Email", value: user.email, icon: "tabler-mail" },
      { title: "Contact", value: user.contact, icon: "tabler-phone" },
    ],
  },
  {
    title: "Work Information",
    icon: "tabler-briefcase",
    items: [
      { title: "Branch", value: user.branch?.name_kh, icon: "tabler-building" },
      {
        title: "Role Name",
        value: user.role?.display_name,
        icon: "tabler-shield-check",
      },
    ],
  },
];

const authStore = useAuthStore();

const uploadRef = ref(null);

// A new pick is a data: URL (shown straight away); a saved one is a path
const photoUrl = computed(() => {
  const path = formData.value.image_path;
  if (!path) return null;
  return path.startsWith("data:") ? path : getImageUrl(path);
});

const lastLogin = computed(() =>
  user.last_login ? moment(user.last_login).fromNow() : "N/A",
);

const onChangeImage = async () => {
  if (formData.value.image_path) {
    isLoading.value = true;
    const res = await api.post("users-change-image-path", {
      image_path: formData?.value.image_path ?? null,
    });

    if (res.data.status) {
      authStore.bootstrap();
    }
    isLoading.value = false;
  }
};

// A photo that fails to load (deleted file, bad path) falls back to the
// initials; a new photo gets a fresh try
const isPhotoBroken = ref(false);

watch(
  () => formData.value.image_path,
  () => {
    isPhotoBroken.value = false;
    onChangeImage();
  },
);
</script>

<template>
  <AppCard :is-header="false" border="border-none">
    <div class="profile-layout">
      <!-- Profile summary -->
      <VCard variant="outlined" class="profile-summary">
        <div class="profile-summary__cover" />

        <div class="profile-summary__body">
          <!-- Round photo; the camera button opens the (hidden) uploader -->
          <div class="avatar">
            <VAvatar
              size="112"
              class="avatar__img"
              color="primary"
              variant="tonal"
            >
              <VImg
                v-if="photoUrl && !isPhotoBroken"
                :src="photoUrl"
                cover
                @error="isPhotoBroken = true"
              />
              <span v-else class="avatar__initial">{{
                avatarText(user.name_kh || user.username || "?")
              }}</span>
            </VAvatar>
            <VBtn
              icon
              size="32"
              color="primary"
              class="avatar__camera"
              :loading="isLoading"
              :aria-label="$t('Change Photo')"
              @click="uploadRef?.openPicker()"
            >
              <VIcon icon="tabler-camera" size="17" />
              <VTooltip activator="parent" location="bottom">{{
                $t("Change Photo")
              }}</VTooltip>
            </VBtn>
          </div>
          <AppImageUpload
            ref="uploadRef"
            v-show="false"
            v-model="formData.image_path"
            icon="tabler-user"
            :url-resolver="getImageUrl"
            :crop-aspect-ratio="1"
            circle-stencil
          />

          <div class="profile-summary__info">
            <h5 class="profile-summary__name">
              {{ user.name_kh || user.username }}
              <!-- Super user: verified-style green check -->
              <VIcon
                v-if="user.is_super"
                icon="tabler-rosette-discount-check-filled"
                color="success"
                size="20"
                :aria-label="$t('Super User')"
              >
                <VTooltip activator="parent" location="top">{{
                  $t("Super User")
                }}</VTooltip>
              </VIcon>
            </h5>
            <span class="profile-summary__username">@{{ user.username }}</span>

            <div class="d-flex flex-wrap justify-center gap-2 mt-3">
              <VChip
                v-if="user.role?.display_name"
                color="primary"
                size="small"
                label
                prepend-icon="tabler-shield-check"
              >
                {{ user.role.display_name }}
              </VChip>
              <VChip
                v-if="user.branch?.name_kh"
                color="secondary"
                size="small"
                label
                prepend-icon="tabler-building"
              >
                {{ user.branch.name_kh }}
              </VChip>
            </div>
          </div>
        </div>

        <!-- Quick stats -->
        <div class="stats">
          <div class="stats__item">
            <VIcon icon="tabler-clock" size="18" class="stats__icon" />
            <span class="stats__label">{{ $t("Last Login") }}</span>
            <span class="stats__value">{{ lastLogin }}</span>
          </div>
          <div class="stats__item">
            <VIcon icon="tabler-shield-check" size="18" class="stats__icon" />
            <span class="stats__label">{{ $t("Role Name") }}</span>
            <span class="stats__value">{{
              user.role?.display_name || "N/A"
            }}</span>
          </div>
        </div>
      </VCard>

      <!-- Details -->
      <div class="profile-details">
        <VCard
          v-for="section in sections"
          :key="section.title"
          variant="outlined"
          class="profile-section"
        >
          <div class="profile-section__header">
            <VIcon :icon="section.icon" size="20" color="primary" />
            <span>{{ $t(section.title) }}</span>
          </div>
          <VDivider />
          <div class="profile-section__grid">
            <div
              v-for="item in section.items"
              :key="item.title"
              class="profile-field"
            >
              <VAvatar color="primary" variant="tonal" rounded size="38">
                <VIcon :icon="item.icon" size="20" />
              </VAvatar>
              <div class="profile-field__text">
                <span class="profile-field__label">{{ $t(item.title) }}</span>
                <span
                  class="profile-field__value"
                  :class="{ 'text-disabled': !item.value }"
                >
                  {{ item.value || "N/A" }}
                </span>
              </div>
            </div>
          </div>
        </VCard>
      </div>
    </div>
  </AppCard>
</template>

<style scoped>
.profile-layout {
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 1.25rem;
  /* stretch: the profile card is as tall as the two info cards beside it */
  align-items: stretch;
}

/* Summary card */
.profile-summary {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.profile-summary__cover {
  height: 96px;
  background:
    radial-gradient(
      circle at 85% 20%,
      rgba(255, 255, 255, 0.25) 0,
      transparent 45%
    ),
    linear-gradient(
      135deg,
      rgb(var(--v-theme-primary)) 0%,
      rgba(var(--v-theme-primary), 0.6) 100%
    );
}

.profile-summary__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  padding: 0 1.25rem 1.25rem;
  margin-top: -56px;
}

/* Name + chips sit in the middle of the space left under the photo */
.profile-summary__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.profile-summary__name {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  margin: 0.875rem 0 0;
  font-family: inter, kantumruy, sans-serif;
  font-size: 18px;
  font-weight: 500;
  line-height: 1.4;
}

.profile-summary__username {
  font-size: 13px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

/* Round photo with a white ring, overlapping the cover */
.avatar {
  position: relative;
}

/* Solid surface behind the tonal tint: without it the initials circle is
   see-through and the cover shows in its top half */
.avatar__img {
  border: 4px solid rgb(var(--v-theme-surface));
  background-color: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
}

.avatar__initial {
  font-size: 36px;
  font-weight: 500;
}

.avatar__camera {
  position: absolute;
  inset-inline-end: 2px;
  bottom: 4px;
  border: 3px solid rgb(var(--v-theme-surface));
}

/* Quick stats */
.stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.stats__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.125rem;
  padding: 0.875rem 0.5rem;
  text-align: center;
}

.stats__item + .stats__item {
  border-inline-start: 1px solid
    rgba(var(--v-border-color), var(--v-border-opacity));
}

.stats__icon {
  margin-bottom: 0.125rem;
  color: rgb(var(--v-theme-primary));
}

.stats__label {
  font-size: 12px;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.stats__value {
  font-size: 13.5px;
  font-weight: 500;
  word-break: break-word;
}

/* Detail sections */
.profile-details {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.profile-section__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1.25rem;
  font-size: 15px;
  font-weight: 600;
}

.profile-section__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem 1.5rem;
  padding: 1.25rem;
}

.profile-field {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  min-width: 0;
}

.profile-field__text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.profile-field__label {
  font-size: 12.5px;
  line-height: 1.4;
  color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.profile-field__value {
  font-size: 15px;
  font-weight: 500;
  line-height: 1.5;
  color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
  word-break: break-word;
}

@media (max-width: 959px) {
  .profile-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 599px) {
  .profile-section__grid {
    grid-template-columns: 1fr;
  }
}
</style>
