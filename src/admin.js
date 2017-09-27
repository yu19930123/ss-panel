import Vue from 'vue'
import VueRouter from 'vue-router'
import VueI18n from 'vue-i18n'
import Admin from './Admin.vue'
import router from './adminRouter'
import store  from './store/'
import {Locales} from './lang'


Vue.use(VueRouter);
Vue.use(VueI18n);

Vue.config.lang = store.state.lang;

Object.keys(Locales).forEach(function (lang) {
    Vue.locale(lang, Locales[lang]);
});

let lang = store.state.lang;

// Ready translated locale messages
// Create VueI18n instance with options
// const i18n = new VueI18n({
//     fallback: 'en',
//     locale: lang, // set locale
//     messages: Locales, // set locale messages
// });

window.App = new Vue({
    router,
    store,
    // i18n,
    el: '#app',
    extends: Admin,
    data: () => ({
        ids: {},
        page: false,
        component: false
    })
});

