<script setup>
import Button from "@/Components/Button.vue";
import {
    IconUserCheck,
    IconUserCancel,
    IconTrashX,
    IconThumbUpFilled,
    IconThumbDownFilled,
    IconPencil,
} from "@tabler/icons-vue";
import { h, ref, watch, watchEffect } from "vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import dayjs from "dayjs";
import Image from 'primevue/image';
import Empty from "@/Components/Empty.vue";
import Skeleton from 'primevue/skeleton';
import { useForm, usePage } from "@inertiajs/vue3";
import { useConfirm } from "primevue/useconfirm";
import { trans, wTrans } from "laravel-vue-i18n";
import { router } from "@inertiajs/vue3";
import { transactionFormat } from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import InputNumber from "primevue/inputnumber";
import Dialog from "primevue/dialog";

const { formatAmount } = transactionFormat();

const postCounts = ref(0);
const posts = ref([]);
const loading = ref(false);

const getResults = async () => {
    loading.value = true;

    try {
        let url = '/highlights/getPosts';

        const response = await axios.get(url);
        posts.value = response.data.posts;
        postCounts.value = response.data.postCounts;
    } catch (error) {
        console.error('Error changing locale:', error);
    } finally {
        loading.value = false;
    }
};

getResults();

const expandedPosts = ref([]);

const toggleExpand = (index) => {
    expandedPosts.value[index] = !expandedPosts.value[index];
};

const formatPostDate = (date) => {
    const now = dayjs();
    const postDate = dayjs(date);

    if (postDate.isSame(now, 'day')) {
        return postDate.format('HH:mm');
    } else if (postDate.isSame(now.subtract(1, 'day'), 'day')) {
        return wTrans('public.yesterday');
    } else {
        return postDate.format('ddd, DD MMM');
    }
};

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const confirm = useConfirm();

const requireConfirmation = (action_type, postId) => {
    const messages = {
        delete_post: {
            group: 'headless',
            color: 'error',
            icon: h(IconTrashX),
            header: trans('public.delete_post'),
            message: trans('public.delete_post_desc'),
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.delete'),
            action: () => {
                router.delete(route('highlights.deletePost', { id: postId }));
            }
        },
    };

    const { group, color, icon, header, message, cancelButton, acceptButton, action } = messages[action_type];

    confirm.require({
        group,
        color,
        icon,
        header,
        message,
        cancelButton,
        acceptButton,
        accept: action
    });
};

const likeCounts = ref({});
const dislikeCounts = ref({});
const likeDeltas = ref({});
const dislikeDeltas = ref({});

const handleLikesCount = (postId, type) => {
    if (!likeCounts.value[postId]) {
        likeCounts.value[postId] = 0;
        likeDeltas.value[postId] = 0;
    }

    if (!dislikeCounts.value[postId]) {
        dislikeCounts.value[postId] = 0;
        dislikeDeltas.value[postId] = 0;
    }

    if (type === 'like') {
        likeCounts.value[postId] += 1;
        likeDeltas.value[postId] += 1;
    } else if (type === 'dislike') {
        dislikeCounts.value[postId] += 1;
        dislikeDeltas.value[postId] += 1;
    }

    // Save likes/dislikes with all necessary data
    saveLikesDebounced(postId, type);
};

// Debounced function to save likes/dislikes
const saveLikesDebounced = debounce((postId, type) => {
    const deltaToSend = type === 'like' ? likeDeltas.value[postId] : dislikeDeltas.value[postId];

    // Reset the delta after getting the value to send
    if (type === 'like') {
        likeDeltas.value[postId] = 0;
    } else if (type === 'dislike') {
        dislikeDeltas.value[postId] = 0;
    }

    axios.post(route('highlights.updateLikeCounts'), {
        id: postId,
        type: type,
        count: deltaToSend,
    })
    .catch((error) => {
        // Rollback on failure
        if (type === 'like') {
            likeDeltas.value[postId] += deltaToSend;
        } else if (type === 'dislike') {
            dislikeDeltas.value[postId] += deltaToSend;
        }
        console.error('Failed to update likes/dislikes:', error);
    });
}, 300);

const isEnlarged = ref(false); // Track if image is enlarged
const imageStyle = ref({
  transform: 'scale(1) translate3d(0, 0, 0)', // Initial transform
  transition: 'transform 0.3s ease', // Transition applied directly
  transformOrigin: 'center center', // Initial transform origin
});

const scaleFactor = 3; // Scale factor for the image

// Toggle enlarged state of the image
const toggleEnlarged = (event) => {
  isEnlarged.value = !isEnlarged.value;

  // Prevent triggering the preview callback by stopping the event
  event.stopImmediatePropagation(); // Prevent the image preview action

  const rect = event.target.getBoundingClientRect();
  const mouseX = event.clientX - rect.left;
  const mouseY = event.clientY - rect.top;

  if (isEnlarged.value) {
    // Adjust transform-origin to focus on the clicked position
    imageStyle.value.transformOrigin = `${(mouseX / rect.width) * 100}% ${(mouseY / rect.height) * 100}%`;

    // Apply the scale transform
    imageStyle.value.transform = `scale(${scaleFactor}) translate3d(0, 0, 0)`;
  } else {
    // Reset transform when not enlarged
    imageStyle.value.transform = 'scale(1) translate3d(0, 0, 0)';
    imageStyle.value.transformOrigin = 'center center'; // Reset transform origin to center
  }
};

// Handle the mouse-follow behavior when enlarged
const followMouse = (event) => {
  if (isEnlarged.value) {
    const rect = event.currentTarget.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;
    const mouseY = event.clientY - rect.top;

    // Amplify the translation values to make the movement more noticeable
    const translateX = ((mouseX / rect.width) - 0.5) * -100 / scaleFactor;
    const translateY = ((mouseY / rect.height) - 0.5) * -100 / scaleFactor;

    imageStyle.value.transform = `scale(${scaleFactor}) translate3d(${translateX}%, ${translateY}%, 0)`;
  }
};

// Reset the image transform to its original state
const resetImageTransform = () => {
  isEnlarged.value = false;
  imageStyle.value.transform = 'scale(1) translate3d(0, 0, 0)';
  imageStyle.value.transformOrigin = 'center center';
};

const form = useForm({
    post_id: null,
    like_amount: 0,
    dislike_amount: 0,
});

const visible = ref(false);

const closeDialog = () => {
    visible.value = false;
}

const editPost = (postData) => {
    form.post_id = postData.id;
    form.like_amount = postData.total_likes_count + parseInt(likeCounts.value[postData.id] || 0);
    form.dislike_amount = postData.total_dislikes_count + parseInt(dislikeCounts.value[postData.id] || 0);

    visible.value = true;

}

const submitForm = () => {
    form.post(route('highlights.editPost'), {
        onSuccess: () => {
            closeDialog();

            if (form.post_id && likeCounts.value[form.post_id] !== undefined) {
                likeCounts.value[form.post_id] = 0;
            }

            if (form.post_id && dislikeCounts.value?.[form.post_id] !== undefined) {
                dislikeCounts.value[form.post_id] = 0;
            }
            form.reset();
        },
    });
}
</script>

<template>
    <div
        v-if="loading"
        class="flex flex-col self-stretch bg-white rounded-lg shadow-card w-full overflow-y-auto"
    >
        <div
            class="border-b border-gray-200 last:border-transparent p-6 flex flex-col gap-5 items-center self-stretch"
        >
            <div class="flex justify-between items-start self-stretch">
                <div class="flex flex-col items-start text-sm">
                    <Skeleton width="9rem" height="0.6rem" class="my-1" borderRadius="2rem"></Skeleton>
                    <Skeleton width="12rem" height="0.5rem" class="my-1" borderRadius="2rem"></Skeleton>
                </div>
                <Skeleton width="2rem" height="0.6rem" class="my-1" borderRadius="2rem"></Skeleton>
            </div>

            <!-- content -->
            <div class="flex flex-col gap-5 items-start self-stretch">
                <Skeleton width="10rem" height="4rem"></Skeleton>
                <div class="flex flex-col gap-3 items-start self-stretch text-sm text-gray-950">
                    <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
                    <Skeleton width="9rem" height="0.6rem" borderRadius="2rem"></Skeleton>
                </div>
            </div>
        </div>
    </div>

    <div
        v-else-if="postCounts === 0 && !posts.length"
        class="flex flex-col items-center justify-center self-stretch bg-white rounded-lg shadow-card w-full overflow-y-auto"
    >
        <Empty
            :title="$t('public.no_posts_yet')"
            :message="$t('public.no_posts_yet_caption')"
        />
    </div>

    <div v-else class="flex flex-col self-stretch bg-white rounded-lg shadow-card w-full overflow-y-auto">
        <div
            v-for="post in posts"
            :key="post.id"
            class="border-b border-gray-200 last:border-transparent px-3 py-5 md:p-6 flex flex-col gap-3 md:gap-5 items-center self-stretch"
        >
            <div class="flex justify-between items-start self-stretch">
                <div class="flex flex-col items-start text-sm">
                    <span class="text-gray-950 font-bold">{{ post.user.first_name }}</span>
                    <span class="text-gray-500">@{{ post.display_name }}</span>
                </div>
                <span class="text-gray-700 text-xs text-right min-w-28">{{ formatPostDate(post.created_at) }}</span>
            </div>

            <!-- content -->
            <div class="flex flex-col gap-5 items-start self-stretch">
                <Image
                    v-if="post.post_attachment"
                    :src="post.post_attachment"
                    alt="Image"
                    image-class="w-60 h-40 object-contain"
                    preview
                    :pt="{
                        toolbar: 'hidden',
                    }"
                    @click="resetImageTransform()"
                >
                    <!-- Original image template with click event -->
                    <template #original>
                        <img
                            :src="post.post_attachment"
                            alt="Image"
                            class="max-h-full object-contain"
                            :class="[isEnlarged ? 'cursor-zoom-out' : 'cursor-zoom-in']"
                            @click="toggleEnlarged($event)"
                            @mousemove="followMouse"
                            :style="imageStyle"
                            data-pc-section="original"
                        />
                    </template>
                </Image>
                <div class="grid grid-cols-1 gap-2 items-start self-stretch text-sm text-gray-950">
                    <span class="font-semibold">{{ post.subject }}</span>
                    <div
                        v-html="post.message"
                        :class="[
                            'prose prose-p:my-0 prose-ul:my-0 max-w-full break-all',
                            {
                                 'max-h-[82px] overflow-hidden text-ellipsis': !expandedPosts[post.id],
                                 'max-h-auto': expandedPosts[post.id],
                            }
                        ]"
                    />
                </div>
                <div
                    class="text-primary font-medium text-sm hover:text-primary-700 select-none cursor-pointer"
                    @click="toggleExpand(post.id)"
                >
                    {{ expandedPosts[post.id] ? $t('public.see_less') : $t('public.see_more') }}
                </div>
            </div>

            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center">
                    <div class="flex justify-center items-center gap-1">
                        <Button
                            type="button"
                            variant="success-text"
                            size="sm"
                            iconOnly
                            pill
                            class="hover:rotate-[-15deg]"
                            @click="handleLikesCount(post.id, 'like')"
                        >
                            <IconThumbUpFilled size="16" stroke-width="1.25" />
                        </Button>
                        <span class="min-w-10 text-gray-700 text-sm">{{ likeCounts[post.id] > 0 ? post.total_likes_count + likeCounts[post.id] : post.total_likes_count }}</span>
                    </div>
                    <div class="flex justify-center items-center gap-1">
                        <Button
                            type="button"
                            variant="error-text"
                            size="sm"
                            iconOnly
                            pill
                            class="hover:rotate-[-15deg]"
                            @click="handleLikesCount(post.id, 'dislike')"
                        >
                            <IconThumbDownFilled size="16" stroke-width="1.25" />
                        </Button>
                        <span class="min-w-10 text-gray-700 text-sm">{{ dislikeCounts[post.id] > 0 ? post.total_dislikes_count + dislikeCounts[post.id] : post.total_dislikes_count }}</span>
                    </div>
                </div>
                <div class="flex justify-center items-center gap-2">
                    <Button
                        type="button"
                        variant="gray-text"
                        size="sm"
                        iconOnly
                        pill
                        @click="editPost(post);"
                    >
                        <IconPencil size="16" stroke-width="1.25" />
                    </Button>
                    <Button
                        type="button"
                        variant="error-text"
                        size="sm"
                        iconOnly
                        pill
                        @click="requireConfirmation('delete_post', post.id);"
                    >
                        <IconTrashX size="16" stroke-width="1.25" />
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.edit')"
        class="dialog-xs md:dialog-sm"
        :dismissableMask="true"
    >
        <div class="flex flex-col md:flex-row py-4 md:py-6 gap-5 md:gap-4 items-start self-stretch">
            <div class="flex flex-col gap-2 w-full">
                <span class="text-sm text-gray-700">{{ $t('public.number_of_likes') }}</span>
                <InputNumber
                    v-model="form.like_amount"
                    inputId="like_amount"
                    inputClass="py-3 px-4"
                    placeholder="0"
                    :min="0"
                    fluid
                />
            </div>
            <div class="flex flex-col gap-2 w-full">
                <span class="text-sm text-gray-700">{{ $t('public.number_of_dislikes') }}</span>
                <InputNumber
                    v-model="form.dislike_amount"
                    inputId="dislike_amount"
                    inputClass="py-3 px-4"
                    placeholder="0"
                    :min="0"
                    fluid
                />
            </div>
        </div>
        <div class="flex flex-row gap-4 justify-center items-center pt-6">
            <Button
                type="button"
                variant="gray-outlined"
                @click="closeDialog()"
                class="w-full"
            >
                {{ $t('public.cancel') }}
            </Button>
            <Button
                type="button"
                variant="primary-flat"
                @click="submitForm()"
                class="w-full"
            >
                {{ $t('public.save') }}
            </Button>
        </div>
    </Dialog>
</template>
