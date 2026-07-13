export const useSidebar = () => {
  return [
    {
      label: "Dashboard",
      children: [
        {
          title: "Dashboard",
          icon: "i-lucide-layout-dashboard",
          to: "/dashboard",
        },
      ],
    },

    {
      label: "Operations",
      children: [
        {
          title: "Transactions",
          icon: "i-lucide-receipt",
          to: "/transactions",
        },
        {
          title: "Stakeholders",
          icon: "i-lucide-users",
          to: "/stakeholders",
        },
        {
          title: "Inventory",
          icon: "i-lucide-package",
          to: "/inventory",
        },
      ],
    },

    {
      label: "Analytics",
      children: [
        {
          title: "Revenue",
          icon: "i-lucide-philippine-peso",
          to: "/revenue",
        },
        {
          title: "Forecasting",
          icon: "i-lucide-chart-line",
          to: "/forecast",
        },
        {
          title: "Reports",
          icon: "i-lucide-file-bar-chart",
          to: "/reports",
        },
      ],
    },

    {
      label: "Administration",
      children: [
        {
          title: "Accounts",
          icon: "i-lucide-user-cog",
          to: "/accounts",
        },
        {
          title: "Settings",
          icon: "i-lucide-settings",
          to: "/settings",
        },
      ],
    },
  ];
};
