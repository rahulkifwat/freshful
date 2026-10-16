{{--
  The admin layout is a CSS table (.content-wrapper = table, sidebar + .dashboard-contents = cells).
  Wide tables would widen the content cell and squeeze the sidebar; max-width:0 + width:100% makes
  the cell take only the remaining space so .table-responsive scrolls instead.
  Above 1024px only: below that the theme switches .dashboard-contents to display:block.
--}}
<style>
  @media (min-width: 1025px) {
    .content-wrapper .dashboard-contents { width: 100%; max-width: 0; }
  }
</style>
