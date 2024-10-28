jQuery(function () {
  function bcMnsDisplayLoading() {
    jQuery(".bc-mns-loading").css("opacity", 1);
  }

  function bcMnsHideLoading() {
    jQuery(".bc-mns-loading").css("opacity", 0);
  }

  function appendPostToSeries(
    postId,
    postTitle,
    editLink,
    viewLink,
    postObject
  ) {
    console.log("append post to series...");
    const html =
      '<li class="single-post-in-series" data-post-id="' +
      postId +
      '">' +
      '<span class="single-post-title">' +
      postTitle +
      " </span>" +
      "<div>" +
      '<span class="bc-mns-post-status ' +
      postObject.post_status +
      '">' +
      postObject.post_status +
      "</span> " +
      '<a target="_blank" class="button-secondary" href="' +
      editLink +
      '">Edit</a>' +
      '<a target="_blank" class="button-secondary" href="' +
      viewLink +
      '">View</a>' +
      '<button class="button-secondary remove-post-from-series" data-post-id="' +
      postId +
      '">Remove</button>' +
      "</div>" +
      "</li>";

    jQuery("#series_posts_list").append(html);
    jQuery("#series_posts_list").sortable({
      revert: true,
    });
  }

  jQuery("#series_posts_list").sortable({
    revert: true,
  });

  jQuery("#search_post_by_title").on("click", ($e) => {
    $e.preventDefault();

    bcMnsDisplayLoading();
    const searchKeyword = jQuery("#bc_mns_keyword").val();
    wp.ajax
      .post("bc_mns_series_fetch_posts", {
        keyword: searchKeyword,
        series_id: window.bc_mns_series_id,
      })
      .done((data) => {
        bcMnsHideLoading();
        //bc_mns_posts_search_results
        let searchResultHTML = "";
        if (Array.isArray(data)) {
          for (let i = 0; i < data.length; i++) {
            //. .
            searchResultHTML +=
              '<li class="single-post-in-series">' +
              '<a class="single-post-title" target="_blank" href="' +
              data[i].guid +
              '">' +
              data[i].post_title +
              "</a> " +
              '<button class="button-secondary add-post-to-series" data-post-id="' +
              data[i].ID +
              '"> Add to series</button>';

            ("<li>");
          }
        }

        searchResultHTML = "<ul >" + searchResultHTML + "</ul>";

        jQuery("#bc_mns_posts_search_results").html(searchResultHTML);
      })
      .fail((e) => {
        bcMnsHideLoading();
        console.log("failed!!!", e);
      });
  });

  jQuery(document).on("click", ".add-post-to-series", function ($e) {
    $e.preventDefault();
    const seriesId = window.bc_mns_series_id;
    const postId = jQuery(this).attr("data-post-id");
    const postTitle = jQuery(this)
      .closest(".single-post-in-series")
      .find("a")
      .first()
      .text();

    const data = { post_id: postId, series_id: seriesId };
    bcMnsDisplayLoading();

    const postToAdd = jQuery(this).closest(".single-post-in-series");

    wp.ajax
      .post("bc_mns_add_post_to_series", data)
      .done((data) => {
        console.log("add post done", data);
        bcMnsHideLoading();

        appendPostToSeries(
          postId,
          postTitle,
          data.post_link,
          data.post_edit_link,
          data.post
        );
        postToAdd.remove();
      })
      .fail((e) => {
        console.log("add post to series failed", e);
        bcMnsHideLoading();
      });
  });

  //remove-post-from-series
  jQuery(document).on("click", ".remove-post-from-series", function ($e) {
    $e.preventDefault();
    const seriesId = window.bc_mns_series_id;

    const postId = jQuery(this).attr("data-post-id");

    const data = { post_id: postId, series_id: seriesId };

    const removeItem = jQuery(this).closest(".single-post-in-series");

    bcMnsDisplayLoading();
    wp.ajax
      .post("bc_mns_remove_post_from_series", data)
      .done((data) => {
        console.log("remove post done", data);
        bcMnsHideLoading();
        removeItem.remove();
      })
      .fail((e) => {
        console.log("remove failed", e);
        bcMnsHideLoading();
      });
  });

  jQuery("#bc_mns_add_new_post_to_series").on("click", ($e) => {
    $e.preventDefault();
    const seriesId = window.bc_mns_series_id;
    const newPostTitle = jQuery("#bc_mns_new_post_title").val();

    if (newPostTitle.trim() == "") {
      console.log("empty post title");
      return;
    }

    const data = { series_id: seriesId, post_title: newPostTitle };

    bcMnsDisplayLoading();

    wp.ajax
      .post("bc_mns_add_new_post_to_series", data)
      .done((data) => {
        console.log("new post added to series", data);
        appendPostToSeries(
          data.post_id,
          newPostTitle,
          data.post_edit_link,
          data.post_link,
          data.post
        );
        bcMnsHideLoading();
      })
      .fail((e) => {
        console.log("update series failed", e);
        bcMnsHideLoading();
      });
  });

  jQuery("#bc_mns_update_series").on("click", ($e) => {
    $e.preventDefault();
    let listPosts = [];
    jQuery("#series_posts_list li").each(function (element) {
      listPosts.push(jQuery(this).attr("data-post-id"));
    });

    const seriesId = window.bc_mns_series_id;

    const data = { post_ids: listPosts, series_id: seriesId };

    bcMnsDisplayLoading();

    wp.ajax
      .post("bc_mns_update_posts_of_series", data)
      .done((data) => {
        console.log("update series done", data);
        bcMnsHideLoading();
      })
      .fail((e) => {
        console.log("update series failed", e);
        bcMnsHideLoading();
      });
  });

  //add post to series in post screen
  jQuery(".in-post-add-post-to-series").on("click", function ($e) {
    $e.preventDefault();

    const postId = jQuery(this).attr("data-post-id");
    const seriesId = jQuery(this).attr("data-series-id");

    const data = { post_id: postId, series_id: seriesId };
    bcMnsDisplayLoading();

    wp.ajax
      .post("bc_mns_add_post_to_series", data)
      .done((data) => {
        console.log("add post done", data);
        bcMnsHideLoading();
        //display post added to series message, hide all available series
        jQuery("#available-series").html("<h3>Post added to series</h3>");
      })
      .fail((e) => {
        console.log("add post to series failed", e);
        bcMnsHideLoading();
      });
  });

  jQuery("#in-post-remove-post-from-series").on("click", function ($e) {
    $e.preventDefault();

    const postId = jQuery(this).attr("data-post-id");
    const seriesId = jQuery(this).attr("data-series-id");

    const data = { post_id: postId, series_id: seriesId };
    bcMnsDisplayLoading();
    wp.ajax
      .post("bc_mns_remove_post_from_series", data)
      .done((data) => {
        console.log("remove post done", data);
        jQuery("#in-post-in-series").html("<h3>Post removed from series</h3>");
        bcMnsHideLoading();
      })
      .fail((e) => {
        console.log("remove failed", e);
        bcMnsHideLoading();
      });
  });
});
