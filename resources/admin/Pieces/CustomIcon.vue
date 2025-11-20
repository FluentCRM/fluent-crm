<template>
    <svg v-if="type == 'company'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
        <line x1="3" y1="9" x2="21" y2="9"/>
        <line x1="9" y1="3" x2="9" y2="21"/>
        <line x1="15" y1="3" x2="15" y2="21"/>
    </svg>
    <svg v-else-if="type == 'campaign'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
        <line x1="3" y1="9" x2="21" y2="9"/>
        <polyline points="15 15 12 18 9 15"/>
    </svg>
    <svg v-else-if="type == 'event-tracking'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 3v18h18"/>
        <path d="M18 9l-5 5-2-2-3 3"/>
        <path d="M15 6h6v6"/>
    </svg>
    <svg v-else-if="type == 'failed-emails'" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <path d="M50 15 L85 80 C87 83 85 85 82 85 L18 85 C15 85 13 83 15 80 L50 15" fill="white" stroke="#e55353" stroke-width="6" stroke-linejoin="round"/>
        <path d="M50 35 L50 60 M50 65 L50 70" stroke="#e55353" stroke-width="6" stroke-linecap="round" />
    </svg>
    
</template>
<script>
export default {
    name: 'CustomIcon',
    props: ['type']
}
</script>
