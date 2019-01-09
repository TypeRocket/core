class ConditionalLogic {


  /**
   * [initializeChangingFields description]
   * @return {null}
   */
  initializeChangingFields($template = null) {

    var $conditions = this.$scope.find('[data-conditions]')

    if($template && !$template.target) $conditions = $template.find('[data-conditions]')

    $conditions.each(function(index, item) {

      if(!$(item).data('conditional-name') && $(item).attr('name')) {
        $(item).attr('data-conditional-name', $(item).attr('name') + '[' + index + ']')
      }

      var conditions = $(item).getConditions(),
        $wrapper = $(item).getOuterWrapper()

      for(var c in conditions) {

        var rules = conditions[c]

        for(var r in rules) {

          var condition = rules[r],
            $field = $wrapper.find('[name*="[' + condition.field + ']"]'),
            conditionalFields = $field.data('conditional-fields') ? $field.data('conditional-fields') : []

          conditionalFields.push($(item).data('conditional-name'))

          $field.addClass('has-conditional-fields').data('conditional-fields', conditionalFields)

        }

      }

    })

    setTimeout(function() {
      jQuery('.has-conditional-fields').each(function() {
        jQuery(this).trigger('change')
      })
    }, 10)

  }


  /**
   * show/hide conditional fields
   * @param  {event} e
   * @return {null}
   */
  toggleFields(e) {

    var $this = $(e.currentTarget),
      conditionalFields = $this.data('conditional-fields')

    for(var i in conditionalFields) {

      var $conditionalField = $('[data-conditional-name="' + conditionalFields[i] + '"]'),
        conditions = $conditionalField.getConditions()

      this.hideField($conditionalField)

      for(var c in conditions) {
        var rules = conditions[c]
        if(this.conditionsMet($this.getOuterWrapper(), rules)) {
          this.showField($conditionalField)
        }
        this.toggleTab($conditionalField.getFieldWrapper())
      }

    }

  }


  /**
   * [hideField description]
   * @param  {jQuery object} $field
   * @return {null}
   */
  hideField($field) {
    $field.getFieldWrapper().hide()
    $field.data('required', $field.prop('required'))
    $field.attr('name', '')
    $field.prop('required', false)
  }


  /**
   * [showField description]
   * @param  {jQuery object} $field
   * @return {null}
   */
  showField($field) {
    $field.getFieldWrapper().show()
    $field.attr('name', $field.data('conditional-name').replace(/\[\d+\]$/, ''))
    $field.prop('required', $field.data('required'))
  }


  /**
   * [conditionsMet description]
   * @param  {jQuery object} $field
   * @param  {array} rules
   * @return {boolean}
   */
  conditionsMet($wrapper, rules) {
    var met = 0
    for(var i in rules) {
      var condition = rules[i],
        $field = $wrapper.find('[name*="[' + condition.field + ']"]'),
        // operator = condition.operator,
        value = condition.value
      if($field.length && this.conditionMet($field, value)) {
        met++
      }
    }
    return met == rules.length
  }


  /**
   * [conditionMet description]
   * @param  {jQuery object} $field
   * @param  {string} value
   * @return {boolean}
   */
  conditionMet($field, value) {
    var type = $field.attr('type'),
      tagName = $field.prop('tagName')
    if(type == 'checkbox' || type == 'radio') {
      if($field.filter(':checked').val() == value) {
        return true
      } else if(type == 'checkbox' && $field.prop('checked')) {
        return true
      }
    } else if(tagName.toLowerCase() == 'select') {
      if($field.find(':selected').val() == value) {
        return true
      }
    }
    return false
  }


  /**
   * [toggleTab description]
   * @param  {jQuery object} $wrapper
   * @return {null}
   */
  toggleTab($wrapper) {
    var $tab = $wrapper.parents('.tab-section').first(),
      $tabLink = jQuery('a[href="#' + $tab.attr('id') + '"]').parent(),
      $visibleElements = $tab.find('> div').filter(function() {
        return $(this).css('display') != 'none'
      })

    $tabLink.hide()
    if($visibleElements.length) $tabLink.show()
  }


  /**
   * [init description]
   * @return {null}
   */
  init() {
    jQuery(window).on('load', this.initializeChangingFields.bind(this))

    jQuery(document).on('change', '.has-conditional-fields', this.toggleFields.bind(this))

    TypeRocket.repeaterCallbacks.push(this.initializeChangingFields.bind(this))
  }


  /**
   * [constructor description]
   * @param {string} scope
   */
  constructor(scope) {
    this.$scope = jQuery(scope)
    this.init()
  }

}


jQuery.fn.extend({
  getConditions: function() {
    return JSON.parse($(this).data('conditions').replace(/'/g, '"'))
  },
  getFieldWrapper: function() {
    return $(this).parents('[class*="typerocket-elements-fields"]').first()
  },
  getOuterWrapper: function() {
    return $(this).parents('.tr-repeater-group, [id*="tab-panel"], .builder-field-group, .postbox-container, #wpbody').first()
  }
})


jQuery(document).ready(function() {
  new ConditionalLogic('body')
})
