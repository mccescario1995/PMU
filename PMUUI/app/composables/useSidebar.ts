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
          children: [
            {
              title: "Inventory",
              icon: "i-lucide-package",
              to: "/inventory/inventory-list",
            },
            {
              title: "Planning",
              icon: "i-lucide-calendar-check",
              to: "/inventory/planning",
            },
          ],
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
          children: [
            {
              title: "Linear Regression",
              icon: "i-lucide-trending-up",
              to: "/forecast/linear-regression",
            },
            {
              title: "AMIRA",
              icon: "i-lucide-brain",
              to: "/forecast/amira",
            },
            {
              title: "SAMIRA",
              icon: "i-lucide-wand-2",
              to: "/forecast/samira",
            },
          ],
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
