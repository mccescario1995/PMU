export default defineAppConfig({
  ui: {
    colors: {
      primary: '#2E4C6C',
    },
    button: {
      compoundVariants: [
        {
          color: 'info',
          class: 'bg-[#2E4C6C] text-white hover:bg-white hover:text-[#2E4C6C]'
        },
        {
          color: 'info',
          variant: 'ghost',
          class: 'bg-white text-[#2E4C6C] hover:bg-[#0f263d] hover:text-white border border-[#2E4C6C]'
        },
        {
          color: 'primary',
          class: 'bg-[#2E4C6C] text-white hover:bg-white hover:text-[#2E4C6C] hover:border hover:border-[#2E4C6C]'
        },
        {
          color: 'secondary',
          class: 'bg-[#2E4C6C] text-white hover:bg-white hover:text-[#2E4C6C] hover:border hover:border-[#2E4C6C]'
        },
      ]
    },
    badge: {
      compoundVariants: [
        {
          color: 'info',
          class: 'bg-[#2E4C6C] text-white hover:bg-white hover:text-[#2E4C6C]'
        },
        {
          color: 'info',
          variant: 'ghost',
          class: 'bg-white text-[#2E4C6C] hover:bg-[#0f263d] hover:text-white border border-[#2E4C6C]'
        },
        {
          color: 'primary',
          class: 'bg-[#2E4C6C] text-white hover:bg-white hover:text-[#2E4C6C] hover:border hover:border-[#2E4C6C]'
        },
        {
          color: 'secondary',
          class: 'bg-[#2E4C6C] text-white hover:bg-white hover:text-[#2E4C6C] hover:border hover:border-[#2E4C6C]'
        },
      ]
    },
    text:{
        primary: '#2E4C6C',
    }
  }
})