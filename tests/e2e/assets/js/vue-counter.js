import { createApp } from 'vue';

const Counter = {
    template: `
        <button type="button" class="btn btn-primary" data-test="vue-counter-btn" @click="count++">
            Clicked {{ count }} times
        </button>
    `,
    data() {
        return { count: 0 };
    },
};

const mountPoint = document.getElementById('vue-counter-app');
if (mountPoint) {
    createApp(Counter).mount(mountPoint);
}
