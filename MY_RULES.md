 Our Frontend Vue JS and Inertia:
    - shadcn-vue for ready to use UI components. In resources/js/components/ui/ folder we have components (card, button, dialog, sonner, button, e.t.c) on top of shadcn-vue components. Try to use them firstly and if there is no what we need, then you can install new one.
    - lucide-vue-next for icons 
    - tw-animate-css for animations
    - in resources/js/lib/utils.ts we have  function cn(...inputs: ClassValue[]) { return twMerge(clsx(inputs)); } for tailwind class merges

Notice: try to use these above mentioned tools. If you need some other 3rd party tools firstly, please ask before installing it and writing the code

    Code Style and Structure
    - Tailwind color variables are located in resources/css/app.css. We have primary, secondary and so on standard colors. Please use these variables. These variables represent our design tokens