$(function () {
  $(".skin-switch a").click(function () {
    if (localStorage.theme == 1) {
      localStorage.theme = 0;
    } else {
      localStorage.theme = 1;
    }
    $("html").attr("data-theme", localStorage.theme);
  });
  $(".head-search").click(function () {
    $(this).addClass("active");
  });
  $(document).click(function (e) {
    if ($(e.target).closest(".head-search").length == 0) {
      $(".head-search").removeClass("active");
    }
  });
});
