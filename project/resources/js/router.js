import Vue from 'vue';
import VueRouter from 'vue-router';

/** Views **/
import PageNotFound from "./views/PageNotFound";

Vue.use(VueRouter);

export default new VueRouter({
    routes: [
        { path: "*", component: PageNotFound }
    ],
    mode: 'history',
});
