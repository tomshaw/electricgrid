<style>
/* ==========================================================================
Table Filters Background
========================================================================== */

    .electricgrid .filters {
        background-image: linear-gradient(45deg,
                #f8f8f8 25%,
                transparent 25%,
                transparent 50%,
                #f8f8f8 50%,
                #f8f8f8 75%,
                transparent 75%,
                transparent);
        background-size: 56.57px 56.57px;
    }

    .dark .electricgrid .filters {
        background-image: linear-gradient(45deg,
                rgba(248, 248, 248, 0.02) 25%,
                transparent 25%,
                transparent 50%,
                rgba(248, 248, 248, 0.02) 50%,
                rgba(248, 248, 248, 0.02) 75%,
                transparent 75%,
                transparent);
        background-size: 56.57px 56.57px;
    }

/* ==========================================================================
Row Hover
========================================================================== */

    .electricgrid tbody tr:hover {
        background-color: var(--eg-row-hover, #f9fafb);
    }

    .dark .electricgrid tbody tr:hover {
        background-color: var(--eg-row-hover-dark, rgba(255, 255, 255, 0.05));
    }
</style>