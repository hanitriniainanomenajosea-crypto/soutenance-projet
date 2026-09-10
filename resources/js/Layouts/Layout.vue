
<template>
    <header class="text-indigo-600">
        <a href="/about">About</a>
         <a :href="route('josea')">Josea</a>
         <a href="/grand">Grand</a>
    </header>
    <main>
        <slot/>
    </main>
    <footer>
        <h1 class="text-red-600">coucou</h1>
    </footer>

</template>