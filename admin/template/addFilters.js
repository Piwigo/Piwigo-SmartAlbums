var addFilter = (function($){
  var count=0,
      limit_count=0,
      level_count=0;

  // MAIN EVENT HANDLERS
  $('#addFilter').change(function() {
    if ($(this).val() != -1) {
      add_filter($(this).val());
      $(this).val(-1);
    }
  });

  $('#removeFilters').click(function() {
    $('#filtersList li').each(function() {
      $(this).remove();
    });

    limit_level=0;
    level_count=0;
    return false;
  });

  $('input[name="is_smart"]').change(function() {
    $('#SmartAlbum_options').toggle();
    $('input[name="countImages"]').toggle();
    $('.count_images_wrapper').toggle();
  });

  $('input[name="countImages"]').click(function() {
    countImages($("#smart"));
    return false;
  });


  // ADD FILTER FUNCTIONS
  function add_filter(type, cond, value) {
    count++;

    var content = $("#filtersRepo #filter_"+type).html().replace(/iiii/g, count);
    $block = $($.parseHTML(content)).appendTo("#filtersList");

    if (cond) {
      select_cond($block, type, cond);
    }

    if (value) {
      if (type == "tags") {
        $block.find(".filter-value .tagSelect").html(value);
      }
      else if (type == "album") {
        select_options($block, value);
      }
      else if (type == "level") {
        select_options($block, value);
      }
      else if (type != "dimensions") {
        $block.find(".filter-value input").val(value);
      }
    }

    init_jquery_handlers($block);

    if (type == "dimensions") {
      select_dimensions($block, cond, value);
    }

    if (type == 'limit') {
      limit_count=1;
      $("#addFilter option[value='limit']").attr('disabled','disabled');
    }
    else if (type == 'level') {
      level_count=1;
      $("#addFilter option[value='level']").attr('disabled','disabled');
    }
  }

  function select_cond($block, type, cond) {
    $block.find(".filter-cond option").removeAttr('selected');
    $block.find(".filter-cond option[value='"+cond+"']").attr('selected', 'selected');
  }

  function select_dimensions($block, cond, value) {
    console.log($block, cond, value);
    cond = cond || 'width';

    $block.find(">.filter-value>span").hide();
    $block.find(".dimension_"+cond).show();

    if (value) {
      values = value.split(',');
    }
    else {
      values = $block.find(".filter_dimension_"+cond+"_slider").slider("values");
    }
    $block.find(".filter_dimension_"+cond+"_slider").slider("values", values);
  }

  function select_options($block, value) {
    values = value.split(',');
    for (j in values) {
      $block.find(".filter-value option[value='"+ values[j] +"']").attr('selected', 'selected');
    }
  }


  // DECLARE JQUERY PLUGINS AND VERSATILE HANDLERS
  function init_jquery_handlers($block) {
    // remove filter
    $block.find(".removeFilter").click(function() {
      type = $(this).next("input").val();
      if (type == 'limit') {
        limit_count=1;
        $("#addFilter option[value='limit']").removeAttr('disabled');
      }
      else if (type == 'level') {
        level_count=1;
        $("#addFilter option[value='level']").removeAttr('disabled');
      }

      $(this).parents('li').remove();
      return false;
    });

    // date filter
    if ($block.hasClass('filter_date')) {
      $block.find("input[type='text']").each(function() {
        $(this).datepicker({
          dateFormat:'yy-mm-dd',
          firstDay:1
        });
      });
    }

    // tags filter
    if ($block.hasClass('filter_tags')) {
      $block.find(".tagSelect").tokenInput(
        [{foreach from=$all_tags item=tag name=tags}{ name:"{$tag.name|escape:javascript}", id:"{$tag.id}" }{if !$smarty.foreach.tags.last},{/if}{/foreach}],
        {
          hintText: '{'Type in a search term'|translate}',
          noResultsText: '{'No results'|translate}',
          searchingText: '{'Searching...'|translate}',
          animateDropdown: false,
          preventDuplicates: true,
          allowFreeTagging: false
        }
      );
    }

    // album filter
    if ($block.hasClass('filter_album')) {
      $block.find(".albumSelect").chosen();
    }

    // dimension filter
    if ($block.hasClass('filter_dimensions')) {
      $block.find(".filter-cond select").change(function() {
        select_dimensions($block, $(this).val());
      });

      $block.find(".filter_dimension_width_slider").slider({
        range: true,
        min: {$dimensions.bounds.min_width},
        max: {$dimensions.bounds.max_width},
        values: [{$dimensions.bounds.min_width}, {$dimensions.bounds.max_width}],
        slide: function(event, ui) {
          change_dimension_info($block, ui.values, "{'between %d and %d pixels'|translate}");
        },
        change: function(event, ui) {
          change_dimension_info($block, ui.values, "{'between %d and %d pixels'|translate}");
        }
      });

      $block.find(".filter_dimension_height_slider").slider({
        range: true,
        min: {$dimensions.bounds.min_height},
        max: {$dimensions.bounds.max_height},
        values: [{$dimensions.bounds.min_height}, {$dimensions.bounds.max_height}],
        slide: function(event, ui) {
          change_dimension_info($block, ui.values, "{'between %d and %d pixels'|translate}");
        },
        change: function(event, ui) {
          change_dimension_info($block, ui.values, "{'between %d and %d pixels'|translate}");
        }
      });

      $block.find(".filter_dimension_ratio_slider").slider({
        range: true,
        step: 0.01,
        min: {$dimensions.bounds.min_ratio},
        max: {$dimensions.bounds.max_ratio},
        values: [{$dimensions.bounds.min_ratio}, {$dimensions.bounds.max_ratio}],
        slide: function(event, ui) {
          change_dimension_info($block, ui.values, "{'between %.2f and %.2f'|translate}");
        },
        change: function(event, ui) {
          change_dimension_info($block, ui.values, "{'between %.2f and %.2f'|translate}");
        }
      });

      $block.find("a.dimensions-choice").click(function() {
        $block.find(".filter_dimension_"+ $(this).data("type") +"_slider").slider("values",
          [$(this).data("min"), $(this).data("max")]
        );
      });
    }
  }


  // GENERAL FUNCTIONS
  function change_dimension_info($block, values, text) {
    $block.find("input[name$='[value][min]']").val(values[0]);
    $block.find("input[name$='[value][max]']").val(values[1]);
    $block.find(".filter_dimension_info").html(sprintf(text, values[0], values[1]));
  }

  function countImages(form) {
    jQuery.post("{$COUNT_SCRIPT_URL}", 'cat_id={$CAT_ID}&'+form.serialize(),
      function success(data) {
        jQuery('.count_images_wrapper').html(data);
      }
    );
  }

  return add_filter; // expose add_filter method
}(jQuery));