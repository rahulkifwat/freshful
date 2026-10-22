{{--
  The admin layout is a CSS table (.content-wrapper = table; sidebar + .dashboard-contents = cells).
  With the default auto table layout, a wide data table squeezes the sidebar down to one letter
  per line. A fixed table layout keeps the sidebar at its CSS width (300/270/250px, 60px when
  collapsed) and gives the content the rest, so .table-responsive scrolls instead.
  Above 1024px only: below that the theme switches .dashboard-contents to display:block.
--}}
<style>
  /* The theme sets white-space:nowrap on every .table cell; long text (addresses) opts back in. */
  .table td.dm-wrap { white-space: normal; word-break: normal; overflow-wrap: break-word; min-width: 220px; max-width: 320px; }

  @media (min-width: 1025px) {
    .content-wrapper { table-layout: fixed; width: 100%; }
  }
</style>
