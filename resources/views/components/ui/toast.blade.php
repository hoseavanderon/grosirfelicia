<div x-data="{
    show: false,
    type: 'success',
    title: '',
    message: ''
}"
    @toast.window="
        type = $event.detail.type;
        title = $event.detail.title;
        message = $event.detail.message;

        show = true;

        setTimeout(() => {
            show = false
        },3000);
    "
    class="fixed top-5 right-5 z-[10000]">

    <div x-show="show" x-transition style="display:none;" class="min-w-[320px] rounded-2xl shadow-xl p-4 text-white"
        :class="{
            'bg-green-600': type === 'success',
            'bg-red-600': type === 'error',
            'bg-yellow-500': type === 'warning',
            'bg-blue-600': type === 'info'
        }">

        <div class="font-semibold" x-text="title"></div>

        <div class="text-sm mt-1" x-text="message"></div>

    </div>

</div>
