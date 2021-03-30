import Vue from 'vue';
import router from './router';

require('./bootstrap');

// Import the styles directly. (Or you could add them via script tags.)
import 'bootstrap/dist/css/bootstrap.css';

new Vue({
    el: '#app',
    router
});
