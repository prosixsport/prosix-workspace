<template>
    <router-view v-slot="{ Component, route }">
        <KeepAlive include="AllOrdersView" :max="1">
            <component
                :is="Component"
                :key="route.name"
            />
        </KeepAlive>
    </router-view>
</template>

<script>
export default {
    name: 'App'
}
</script>
