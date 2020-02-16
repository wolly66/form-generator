<?php

/**
 * Class Wol_Form_Generator
 *
 * This class generate forms with various input element for frontend application
 *
 * Based on wpit form generator
 * By SteveAgl aka Stefano Aglietti
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Wolbusinessdesk_Form_Generator {

	/**
	 * Counter for the tabindex
	 *
	 * @since  1.0.0
	 * @access public
	 * @var int
	 */
	public $tabindex_counter;

	/**
	 * Set the increment for tabindex counter
	 *
	 * @since  1.0.0
	 * @access public
	 * @var int
	 */
	public $tabindex_counter_increment;

	/**
	 * Keep the form name
	 *
	 * @since  1.0.0
	 * @access public
	 * @var string
	 */
	public $form_name;

	/**
	 * Flag if keep old values for each field so in submit the procedure can check if the field
	 * is changed and should be updated or not. Usuefull in complex form when only few values chamges
	 * so there is no need to do lot of update queries
	 *
	 * @since  1.0.0
	 * @access public
	 * @var boolean
	 */
	public $keep_old_values;

	/**
	 * Flag if keep old values for the curent form bypassin the declaration for the calls instantiation
	 *
	 * @since  1.0.0
	 * @access public
	 * @var boolean
	 */
	public $form_old_values;

	/**
	 * Keeps the old values for the inserted fileds of the form
	 *
	 * @since  1.0.0
	 * @access private
	 * @var array key=>value with key the field id and value the old value
	 */
	private $fields_old_values;

	/**
	 * Keeps the old values for the inserted fileds of the form
	 *
	 * @since  1.0.0
	 * @access private
	 * @var array key=>value with key the field id and value the old value
	 */
	private $default_echo;

	/**
	 * Keeps data for every datepicker to generate the js script
	 *
	 * @since  1.0.0
	 * @access private
	 * @var array key=>value with key the field id and value the old value
	 */
	private $datepicker_data;

	/**
	 * Class constructor
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array|string $args array of paramenter or an URL query type string
	 *
	 */
	public function __construct( $args = '' ) {

		$defaults = array(
			'keep_old_values' => FALSE,
			'default_echo'   => TRUE,
			'form_textdomain' => 'form_textdomain',
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Set if keep or values or not
		$this->keep_old_values = $args['keep_old_values'];
		// Set the defaul echo for all elements
		$this->default_echo = $args['default_echo'];

		// Init tabindex counter to 0
		$this->tabindex_counter = 0;

		// Set tabindex increment to 10
		$this->tabindex_counter_increment = 10;

		// Init datepickers data to empty array
		$this->datepicker_data = array();

	}

	/**
	 * Close the form
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args
	 *     Optional. Array or string for closing form parameters.
	 *
	 *     @type bool $echo       Set if the generate html fragment of code should be echo or not
	 *                            by default it's true.
	 *
	 * @return mixed none on echo or the html string to print
	 */
	public function close_form( $args = '' ) {

		$defaults = array(
			'echo' => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Defore closing form if there is datepickers alternate fields add them as hidden
		$html_datepickers_alternate_fields = $this->datepickers_alternate_fields( $args['echo'] );

		// Before closing form if there is old values to pass add the hidden field
		if ( $this->form_old_values && $this->keep_old_values ) {
			$html_hidden_values = $this->add_old_data_to_form( $args['echo'] );
		} else {
			$html_hidden_values = '';
		}

		// After closing form add the javascript to set che datepickes if presents
		$html_datepicker_scripts = $this->create_datepickers_scripts( $args['echo'] );

		$html_special_hiddens = '';

		$html_special_hiddens .= $html_datepickers_alternate_fields;
		$html_special_hiddens .= $html_hidden_values;

		$html = $html_special_hiddens;

		$html .= sprintf(
			'</form><!-- Closing %1$s form -->',
			$this->form_name // 1
		);

		$html .= $html_datepicker_scripts;

		if ( $args['echo'] ) {

			echo $html;

			return null;

		} else {

			return $html;
		}

	}

	/**
	 * Open the form
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args {
	 *     Optional. Array or string for open form parameters.
	 *
	 *     @type int    $id                  The form id .
	 *     @type string $name                The form name .
	 *     @type string $method              The method used to submit form only POST or GET, default to POST
	 *     @type string $action              The action associated to the form.
	 *     @type string $class               One or more classes to apply to the form element.
	 *     @type string $enctype             The type of encoding for the form submission.
	 *     @type string $tabindex_increment  The value to use to increment tabindex value.
	 *     @type string $old_value           Avoid saving old values for this specific form.
	 *     @type bool $echo                  Set if the generate html fragment of code should be echo or not
	 *                                       by default it's true.
	 *
	 * @return string echo or return the code to open the form
	 */
	public function open_form( $args ) {

		$defaults = array(
			'id'                 => '',
			'name'               => '',
			'method'             => 'POST',
			'action'             => '',
			'class'              => '',
			'enctype'            => 'multipart/form-data',
			'tabindex_increment' => 0,
			'old_value'          => $this->keep_old_values,
			'echo'               => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors[] = __( 'Missing the id or the name for the open form tag', 'wpit-formgen' );
		}

		// Check a correct method
		$args['method'] = strtoupper( $args['method'] );

		if ( ! in_array( $args['method'], array( 'POST', 'GET' ) ) ) {
			$errors[] = __( 'Wrong method tye should be POST or GET', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// If is passed a tab index increment set it

		if ( isset( $args['tabindex_increment'] ) && 0 != $args['tabindex_increment'] ) {
			$this->tabindex_counter_increment = $args['tabindex_increment'];
		}

		$this->form_name = $args['name'];

		$this->form_old_values = $args['old_value'];

		$html = sprintf(
			'<!-- Opening %1$s form --><form id="%1$s" name="%2$s" method="%3$s" action="%4$s" class="%5$s" enctype="%6$s">',
			$args['id'], // 1
			$args['name'], // 2
			$args['method'], // 3
			$args['action'], // 4
			$args['class'], // 5
			$args['enctype'] // 6
		);

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate Input text
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args        {
	 *                                  Optional. Array or string for input text field parameters.
	 *
	 * @type int           $id          The id of the input element.
	 * @type string        $name        The name of the input element.
	 * @type string        $label       The optional label to add to the input element
	 * @type int|string    $mandatory   If the input element is mandatory set the specified message.
	 * @type string        $placeholder The placeholder text for the input element.
	 * @type string        $class       One or more classes to apply to the input element.
	 * @type string        $value       The value to assign to the input element.
	 * @type bool          $echo        Set if the generate html fragment of code should be echo or not
	 *                                    by default it's true.
	 *
	 * @return string echo or return the code the text imput form element
	 */
	public function input_text( $args = '' ) {

		$defaults = array(
			'id'          => '',
			'name'        => '',
			'label'       => '',
			'mandatory'   => 0,
			'placeholder' => '',
			'class'       => '',
			'value'       => '',
			'echo'        => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors[] = __( 'Missing the id or the name for the input text tag', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}
		$required = ( $args['mandatory'] )?
			'required':
			'';
			
		// Generate the block
		$html = sprintf(
			'<input type="text" id="%1$s" name="%2$s" tabindex="%3$s" class="%4$s" placeholder="%5$s" value="%6$s" %7$s/>',
			$args['id'], // 1
			esc_attr( $args['name'] ), // 2
			$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment, // 3
			$args['class'], // 4
			$args['placeholder'], // 5
			esc_attr( $args['value'] ), // 6
			esc_attr( $required ) // 7
		);

		// Check if put a label tab before the impute text
		if ( '' != $args['label'] ) {
			$html_label = sprintf(
				'<label for="%1$s">%2$s %3$s</label>',
				esc_attr( $args['name'] ), // 1
				esc_attr( $args['label'] ), // 2
				( $args['mandatory'] ) ? '<span class="wol-mandatory">' . esc_attr( $args['mandatory'] ) . '</span>' : ''
			// 3
			);

			$html = $html_label . $html;
		}

		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $args['value'] );

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate textarea
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args        {
	 *                                  Optional. Array or string for textarea element parameters.
	 *
	 * @type int           $id          The id of the input element.
	 * @type string        $name        The name of the input element.
	 * @type string        $rows        The number of rows for the input element.
	 * @type string        $cols        The number of columns for the input element.
	 * @type string        $label       The optional label to add to the input element
	 * @type int|string    $mandatory   If the input element is mandatory set the specified message.
	 * @type string        $class       One or more classes to apply to the input element.
	 * @type string        $value       The value to assign to the input element.
	 * @type bool          $echo        Set if the generate html fragment of code should be echo or not
	 *                                  by default it's true.
	 *
	 * @return string echo or return the code the textarea form element
	 */
	public function input_textarea( $args = '' ) {

		$defaults = array(
			'id'        => '',
			'name'      => '',
			'rows'      => 5,
			'cols'      => 50,
			'label'     => '',
			'mandatory' => 0,
			'class'     => '',
			'value'     => '',
			'echo'      => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors[] = __( 'Missing the id or the name for the input textarea tag', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}
		
		$required = ( $args['mandatory'] )?
			'required':
			'';
		// Generate the block
		$html = sprintf(
			'<textarea id="%1$s" name="%2$s" rows="%3$s" cols="%4$s" tabindex="%5$s" class="%6$s" %8$s>%7$s</textarea>',
			$args['id'], // 1
			esc_attr( $args['name'] ), // 2
			absint( esc_attr( $args['rows'] ) ), // 3
			absint( esc_attr( $args['cols'] ) ), // 4
			$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment, // 5
			$args['class'], // 6
			esc_attr( $args['value'] ), // 7
			esc_attr( $required )
		);

		// Check if put a label tab before the impute text
		if ( '' != $args['label'] ) {
			$html_label = sprintf(
				'<label for="%1$s">%2$s %3$s</label>',
				esc_attr( $args['name'] ), // 1
				esc_attr( $args['label'] ), // 2
				( $args['mandatory'] ) ? '<span class="wol-mandatory">' . esc_attr( $args['mandatory'] ) . '</span>' : ''
			// 3
			);

			$html = $html_label . $html;
		}

		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $args['value'] );


		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate checkbox
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args {
	 *     Optional. Array or string for input checkboxes parameters.
	 *
	 *     @type string  $id           The id for the checkboxes array.
	 *     @type string  $name         The name for the checkboxes array.
	 *     @type string  $class        One or more classes to apply to the input element.
	 *     @type string  $tocheck      The value that a checkbox has to have to be marcked checkd.
	 *     @type array   $checkboxes   Associative array with paramenter for each checkbox.
	 *                       @type string  $key    The key of the associative array is the text that
	 *                                             show near the checkbox.
	 *                       @type string  $value  The checkbox value.
	 *     @type string  $separator    The string or html code to separate checkboxes eachother
	 *                                 by defaul is a <br>
	 *     @type array   $wrapper      A two element array to wrap every checkbox
	 *                       @type string  $key    The key is 'before' and 'after'
	 *                       @type string  $value  The html code to pur before and after the checkbox.
	 *     @type bool    $echo         Set if the generate html fragment of code should be echo or not
	 *                                 by default it's true.
	 *
	 * @return string echo or return the code the one or more checkbox elements
	 */
	public function input_checkbox( $args = '' ) {

		$defaults = array(
			'id'         => '',
			'name'       => '',
			'class'      => '',
			'tocheck'    => array(),
			'checkboxes' => array(),
			'separator'  => '',
			'wrapper'    => array(),
			'echo'       => $this->default_echo,
			'legend'	 => '',
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors[] = __( 'Missing the id or the name for the checkboxes', 'wpit-formgen' );
		}

		// Check if there is some checkboxes
		if ( ! isset ( $args['checkboxes'] ) || 0 == count( $args['checkboxes'] ) ) {
			$errors[] = __( 'No checkboxes defined', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// Set started counter for id attribute
		$hidden_id_counter = 0;
		$html              = '';
		$old_values        = array();

		// Generate the block
		foreach ( $args['checkboxes'] as $check_value => $check_label ) {

			$checked = '';
			if ( is_array( $args['tocheck'] ) && ! empty ( $args['tocheck'] ) ) {
				foreach ( $args['tocheck'] as $arg_tocheck ) {
					if ( $check_value == $arg_tocheck ) {
						$checked = 'checked="checked"';
						break;
					}
				}
			} else {
				$checked = ( $check_value ==  $args['tocheck'] ) ? 'checked="checked"' : '';
			}

			// Generate the block
			$html_field = sprintf(
				'<input id="check_%2$s_%1$s" name="%2$s[]" class="%8$s" type="checkbox" value="%4$s" tabindex="%7$s" %6$s > <label for="check_%2$s_%1$s">%3$s</label> %5$s',
				$hidden_id_counter ++, // 1
				esc_attr( $args['name'] ), // 2
				esc_attr( $check_label ), // 3
				esc_attr( $check_value ), // 4
				$args['separator'], // 5
				$checked, // 6
				$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment, // 7
				esc_attr( $args['class'] ) // 9
			);

			if ( ! empty ( $args['wrapper'] )  ) {

				$html_field = sprintf(
					'%1$s %2$s %3$s',
					$args['wrapper']['before'], // 1
					$html_field, // 2
					$args['wrapper']['after'] // 3
				);

			}
			
			

			$html .= $html_field;
			

			if ( ! is_array( $args['tocheck'] ) ) {
				$args['tocheck'] = (array) $args['tocheck'];
			}

			if ( in_array( $check_value, $args['tocheck'] ) ) {
				$old_values[] = esc_attr( $check_value );
			}

		}
		
		// Check if put a legend tab before the impute text
			if ( '' != $args['legend'] ) {
			$html_label = sprintf(
				'<legend>%1$s</legend>',
				esc_attr( $args['legend'] ) // 1
			);

			$html = $html_label . $html;
			}
		// Add fieldset
		
			$html = '<fieldset>' . $html . '</fieldset>';
		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $old_values );

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate radio buttons
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args {
	 *     Optional. Array or string for input radio buttons parameters.
	 *
	 *     @type string  $name           The name for the radio buttons array.
	 *     @type string  $class          One or more classes to apply to the input element.
	 *     @type string  $tocheck        The value that a radio button has to have to be marked checked.
	 *     @type array   $radiobuttons   Associative array with paramenter for each radio button.
	 *                       @type string  $key    The key of the associative array is the text that
	 *                                             show near the checkbox.
	 *                       @type string  $value  The checkbox value.
	 *     @type string  $separator      The string or html code to separate radio buttons eachother
	 *                                   by defaul is a <br>
	 *     @type array   $wrapper        A two element array to wrap every radio button
	 *                       @type string  $key    The key is 'before' and 'after'
	 *                       @type string  $value  The html code to pur before and after the radio button.
	 *     @type bool    $echo           Set if the generate html fragment of code should be echo or not
	 *                                   by default it's true.
	 *
	 * @return string echo or return the code for the adio buttons elements
	 */
	public function input_radio( $args = '' ) {

		$defaults = array(
			'name'         => '',
			'class'        => '',
			'tocheck'      => '',
			'radiobuttons' => array(),
			'separator'    => '<br>',
			'wrapper'      => array(),
			'echo'         => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check name
		if ( '' == $args['name'] ) {
			$errors[] = __( 'Missing the name for the radio buttons', 'wpit-formgen' );
		}

		// Check if there is some checkboxes
		if ( ! isset ( $args['radiobuttons'] ) || 0 == count( $args['radiobuttons'] ) ) {
			$errors[] = __( 'No radio buttons defined', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// Set started counter for id attribute
		$html = '';

		// Generate the block
		foreach ( $args['radiobuttons'] as $check_label => $check_value ) {

			$checked = ( $check_value == $args['tocheck'] ) ? 'checked="checked"' : '';

			// Generate the block
			$html_field = sprintf(
				'<label><input name="%1$s" type="radio" class="%2$s" value="%3$s" tabindex="%7$s" %4$s>%5$s</label>%6$s',
				esc_attr( $args['name'] ), // 1
				$args['class'], // 2
				esc_attr( $check_value ), // 3
				$checked, // 4
				esc_attr( $check_label ), // 5
				$args['separator'], // 6
				$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment // 7
			);

			if ( ! empty ( $args['wrapper'] ) ) {

				$html_field = sprintf(
					'%1$s %2$s %3$s',
					$args['wrapper']['before'], // 1
					$html_field, // 2
					$args['wrapper']['after'] // 3
				);
			}

			$html .= $html_field;

		}

		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $args['tocheck'] );


		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate dropdown
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args           {
	 *                                     Optional. Array or string for input radio buttons parameters.
	 *
	 * @type string        $id             The id for the dropdown.
	 * @type string        $name           The name for the dropdown.
	 * @type string        $class          One or more classes to apply to the input element.
	 * @type string        $label          One or more classes to apply to the input element.
	 * @type string        $mandatory      If the input element is mandatory set the specified message.
	 * @type string        $tocheck        The value that will be marked as selected.
	 * @type array         $values         An array with alla possible values for the dropdown.
	 * @type array         $no_check_label A value to add as first option with selected disabled to
	 *                                  have a initial value when any other element is not selected.
	 *
	 * @type bool          $echo           Set if the generate html fragment of code should be echo or not
	 *                          by default it's true.
	 *
	 * @return string echo or return the code for the dropdown element
	 */
	public function input_dropdown( $args = '' ) {

		$defaults = array(
			'id'             => '',
			'name'           => '',
			'class'          => '',
			'label'          => '',
			'mandatory'      => '',
			'tocheck'        => '',
			'values'         => array(),
			'no_check_label' => '',
			'echo'           => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors = __( 'Missing the id or the name for the dropdown element', 'wpit-formgen' );
		} elseif ( ! isset ( $args['values'] ) || 0 == count( $args['values'] ) ) {
			$errors = __( 'No values for the dropdown defined', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {

			if ( $args['echo'] ) {
				echo $errors;

				return FALSE;
			} else {
				return $errors;
			}

		}

		// Set started counter for id attribute
		$html = '';

		// Open the dropdown element

		// Check if put a label tab before the input text
		if ( '' != $args['label'] ) {
			$html_label = sprintf(
				'<label for="%1$s">%2$s %3$s</label>',
				esc_attr( $args['name'] ), // 1
				esc_attr( $args['label'] ), // 2
				( $args['mandatory'] ) ? '<span class="wol-mandatory">' . esc_attr( $args['mandatory'] ) . '</span>' : ''
			// 3
			);

			$html = $html_label . $html;
		}

		// Add the select open tag
		$html_fragment = sprintf(
			'<select id="%1$s" name="%2$s" class="%3$s" tabindex="%4$s">',
			esc_attr( $args['id'] ), // 1
			esc_attr( $args['name'] ), // 2
			$args['class'], // 3
			$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment // 4
		);

		$html .= $html_fragment;

		// Add a non selectable label when no value is checked
		if ( '' != $args['no_check_label'] ) {
			// Generate the block
			$html_field = sprintf(
				'<option selected disabled>%1$s</option>',
				$args['no_check_label'] // 1
			);

			$html .= $html_field;

		}

		// Generate the block
		foreach ( $args['values'] as $check_value => $check_label ) {

			$select = ( $check_value == $args['tocheck'] ) ? 'selected' : '';

			// Generate the block
			$html_field = sprintf(
				'<option value="%1$s" %2$s >%3$s</option>',
				esc_attr( $check_value ), // 1
				$select, // 2
				esc_attr( $check_label ) // 3
			);

			$html .= $html_field;

		}

		// Close the dropdown element
		$html .= "</select>";

		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $args['tocheck'] );

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}
	
	public function input_dropdown_wp_tax( $args = '' ) {
		
		$html = '';
		
		$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment;
		
		$defaults = array(
        	'show_option_all'   => '',
        	'show_option_none'  => '',
        	'orderby'           => 'id',
        	'order'             => 'ASC',
        	'show_count'        => 0,
        	'hide_empty'        => 1,
        	'child_of'          => 0,
        	'exclude'           => '',
        	'echo'              => 1,
        	'selected'          => 0,
        	'hierarchical'      => 0,
        	'name'              => 'cat',
        	'id'                => '',
        	'class'             => 'postform',
        	'depth'             => 0,
        	'tab_index'         => $this->tabindex_counter,
        	'taxonomy'          => 'category',
        	'hide_if_empty'     => false,
        	'option_none_value' => -1,
        	'value_field'       => 'term_id',
        	'required'          => false,
        	'label'				=> '',
		);
		
		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );
		
		// Check if put a label tab before the input text
		if ( '' != $args['label'] ) {
			$html_label = sprintf(
				'<label for="%1$s">%2$s %3$s</label>',
				esc_attr( $args['id'] ), // 1
				esc_attr( $args['label'] ), // 2
				( $args['mandatory'] ) ? '<span class="wol-mandatory">' . esc_attr( $args['mandatory'] ) . '</span>' : ''
			// 3
			);

			$html = $html_label;
		}
		$html .= wp_dropdown_categories( $args );
		
		return $html;
	}
	/**
	 * Generate dropdown for italian province
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args           {
	 *                                     Optional. Array or string for input radio buttons parameters.
	 *
	 * @type string        $id             The id for the dropdown.
	 * @type string        $name           The name for the dropdown.
	 * @type string        $class          One or more classes to apply to the input element.
	 * @type string        $label          One or more classes to apply to the input element.
	 * @type string        $mandatory      If the input element is mandatory set the specified message.
	 * @type string        $tocheck        The value that will be marked as selected.
	 * @type array         $no_check_label A value to add as first option with selected disabled to
	 *                                  have a initial value when any other element is not selected.
	 *
	 * @type bool          $echo           Set if the generate html fragment of code should be echo or not
	 *                          by default it's true.
	 *
	 * @return string echo or return the code for the dropdown element
	 */

	public function input_dropdown_prov( $args = '' ) {

		$defaults = array(
			'id'             => '',
			'name'           => '',
			'class'          => '',
			'label'          => '',
			'mandatory'      => '',
			'tocheck'        => '',
			'values'         => array(),
			'no_check_label' => 'Seleziona la provincia',
			'echo'           => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors = __( 'Missing the id or the name for the dropdown element', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {

			if ( $args['echo'] ) {
				echo $errors;

				return FALSE;
			} else {
				return $errors;
			}

		}

		// Populate the values array with italina province
		$args['values'] = array(
			'Torino'                => 'Torino',
			'Vercelli'              => 'Vercelli',
			'Novara'                => 'Novara',
			'Cuneo'                 => 'Cuneo',
			'Asti'                  => 'Asti',
			'Alessandria'           => 'Alessandria',
			'Biella'                => 'Biella',
			'Verbano-Cusio-Ossola'  => 'Verbano-Cusio-Ossola',
			'Valle d\'Aosta'        => 'Valle d\'Aosta',
			'Varese'                => 'Varese',
			'Como'                  => 'Como',
			'Sondrio'               => 'Sondrio',
			'Milano'                => 'Milano',
			'Bergamo'               => 'Bergamo',
			'Brescia'               => 'Brescia',
			'Pavia'                 => 'Pavia',
			'Cremona'               => 'Cremona',
			'Mantova'               => 'Mantova',
			'Lecco'                 => 'Lecco',
			'Lodi'                  => 'Lodi',
			'Monza Brianza'         => 'Monza Brianza',
			'Bolzano'               => 'Bolzano',
			'Trento'                => 'Trento',
			'Verona'                => 'Verona',
			'Vicenza'               => 'Vicenza',
			'Belluno'               => 'Belluno',
			'Treviso'               => 'Treviso',
			'Venezia'               => 'Venezia',
			'Padova'                => 'Padova',
			'Rovigo'                => 'Rovigo',
			'Udine'                 => 'Udine',
			'Gorizia'               => 'Gorizia',
			'Trieste'               => 'Trieste',
			'Pordenone'             => 'Pordenone',
			'Imperia'               => 'Imperia',
			'Savona'                => 'Savona',
			'Genova'                => 'Genova',
			'La Spezia'             => 'La Spezia',
			'Piacenza'              => 'Piacenza',
			'Parma'                 => 'Parma',
			'Reggio Emilia'         => 'Reggio Emilia',
			'Modena'                => 'Modena',
			'Bologna'               => 'Bologna',
			'Ferrara'               => 'Ferrara',
			'Ravenna'               => 'Ravenna',
			'Forlì'                 => 'Forlì',
			'Rimini'                => 'Rimini',
			'Massa Carrara'         => 'Massa Carrara',
			'Lucca'                 => 'Lucca',
			'Pistoia'               => 'Pistoia',
			'Firenze'               => 'Firenze',
			'Livorno'               => 'Livorno',
			'Pisa'                  => 'Pisa',
			'Arezzo'                => 'Arezzo',
			'Siena'                 => 'Siena',
			'Grosseto'              => 'Grosseto',
			'Prato'                 => 'Prato',
			'Perugia'               => 'Perugia',
			'Terni'                 => 'Terni',
			'Pesaro e Urbino'       => 'Pesaro e Urbino',
			'Ancona'                => 'Ancona',
			'Macerata'              => 'Macerata',
			'Ascoli Piceno'         => 'Ascoli Piceno',
			'Fermo'                 => 'Fermo',
			'Viterbo'               => 'Viterbo',
			'Rieti'                 => 'Rieti',
			'Roma'                  => 'Roma',
			'Latina'                => 'Latina',
			'Frosinone'             => 'Frosinone',
			'L\'Aquila'             => 'L\'Aquila',
			'Teramo'                => 'Teramo',
			'Pescara'               => 'Pescara',
			'Chieti'                => 'Chieti',
			'Campobasso'            => 'Campobasso',
			'Isernia'               => 'Isernia',
			'Caserta'               => 'Caserta',
			'Benevento'             => 'Benevento',
			'Napoli'                => 'Napoli',
			'Avellino'              => 'Avellino',
			'Salerno'               => 'Salerno',
			'Foggia'                => 'Foggia',
			'Bari'                  => 'Bari',
			'Taranto'               => 'Taranto',
			'Brindisi'              => 'Brindisi',
			'Lecce'                 => 'Lecce',
			'Barletta-Andria-Trani' => 'Barletta-Andria-Trani',
			'Potenza'               => 'Potenza',
			'Matera'                => 'Matera',
			'Cosenza'               => 'Cosenza',
			'Catanzaro'             => 'Catanzaro',
			'Reggio Calabria'       => 'Reggio Calabria',
			'Crotone'               => 'Crotone',
			'Vibo Valentia'         => 'Vibo Valentia',
			'Trapani'               => 'Trapani',
			'Palermo'               => 'Palermo',
			'Messina'               => 'Messina',
			'Agrigento'             => 'Agrigento',
			'Caltanissetta'         => 'Caltanissetta',
			'Enna'                  => 'Enna',
			'Catania'               => 'Catania',
			'Ragusa'                => 'Ragusa',
			'Siracusa'              => 'Siracusa',
			'Sassari'               => 'Sassari',
			'Nuoro'                 => 'Nuoro',
			'Cagliari'              => 'Cagliari',
			'Oristano'              => 'Oristano',
			'Olbia-Tempio'          => 'Olbia-Tempio',
			'Ogliastra'             => 'Ogliastra',
			'Medio Campidano'       => 'Medio Campidano',
			'Carbonia-Iglesias'     => 'Carbonia-Iglesias',
		);

		ksort( $args['values'] );

		// Set started counter for id attribute
		$html = '';

		// Open the dropdown element

		// Check if put a label tab before the input text
		if ( '' != $args['label'] ) {
			$html_label = sprintf(
				'<label for="%1$s">%2$s %3$s</label>',
				esc_attr( $args['name'] ), // 1
				esc_attr( $args['label'] ), // 2
				( $args['mandatory'] ) ? '<span class="wol-mandatory">' . esc_attr( $args['mandatory'] ) . '</span>' : ''
			// 3
			);

			$html = $html_label . $html;
		}

		// Add the select open tag
		$html_fragment = sprintf(
			'<select id="%1$s" name="%2$s" class="%3$s" tabindex="%4$s">',
			esc_attr( $args['id'] ), // 1
			esc_attr( $args['name'] ), // 2
			$args['class'], // 3
			$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment // 4
		);

		$html .= $html_fragment;

		// Add a non selectable label when no value is checked
		if ( '' != $args['no_check_label'] ) {
			// Generate the block
			$html_field = sprintf(
				'<option selected disabled>%1$s</option>',
				$args['no_check_label'] // 1
			);

			$html .= $html_field;

		}

		// Generate the block
		foreach ( $args['values'] as $check_label => $check_value ) {

			$select = ( $check_value == $args['tocheck'] ) ? 'selected' : '';

			// Generate the block
			$html_field = sprintf(
				'<option value="%1$s" %2$s >%3$s</option>',
				esc_attr( $check_value ), // 1
				$select, // 2
				esc_attr( $check_label ) // 3
			);

			$html .= $html_field;

		}

		// Close the dropdown element
		$html .= "</select>";

		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $args['tocheck'] );

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}
	/**
	 * Generate Datepicker jQuery UI
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args      {
	 *                                Optional. Array or string for datepicker parameters.
	 *
	 *      @type string  $id            The id for the dropdown.
	 *      @type string  $name          The name for the dropdown.
	 *      @type string  $label         One or more classes to apply to the input element.
	 *      @type string  $mandatory     If the input element is mandatory set the specified message.
	 *      @type string  $placeholder   If the input element is mandatory set the specified message.
	 *      @type string  $class         One or more classes to apply to the input element.
	 *      @type string  $value         The value to assign to the field
	 *      @type array   $alt_field     An array with the paramenter for the alternative date
	 *                                   field to have a different date format to store.
	 *                       @type string $id      The id for alternative field
	 *                       @type string $name    The name for alternative field
	 *                       @type string $value   The name for alternative field
	 *                       @type string $format  The name for alternative field
	 *
	 *      @type string  $script_param  An array of key, value with the key for the name of the
	 *                                   parameter and the value for the the value of the parameter
	 *
	 * @type bool          $echo         Set if the generate html fragment of code should be echo or not
	 *                                   by default it's true.
	 *
 	 * @return string echo or return the code the text imput form element
	 */
	public function input_datepicker( $args = '' ) {

		$defaults = array(
			'id'           => '',
			'name'         => '',
			'label'        => '',
			'mandatory'    => 0,
			'placeholder'  => '',
			'class'        => '',
			'value'        => '',
			'alt_field'    => array(
				'id'     => '',
				'name'   => '',
				'value'  => '',
				'format' => '',
			),
			'script_param' => array(
			),
			'echo'         => $this->default_echo,
		);


		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors[] = __( 'Missing the id or the name for the datepicker element', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// Generate the block
		$html = sprintf(
			'<input type="text" id="%1$s" name="%2$s" tabindex="%3$s" class="%4$s" placeholder="%5$s" value="%6$s" />',
			$args['id'], // 1
			esc_attr( $args['name'] ), // 2
			$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment, // 3
			$args['class'], // 4
			$args['placeholder'], // 5
			esc_attr( $args['value'] ) // 6
		);

		// Check if put a label tab before the impute text
		if ( '' != $args['label'] ) {
			$html_label = sprintf(
				'<label for="%1$s">%2$s %3$s</label>',
				esc_attr( $args['name'] ), // 1
				esc_attr( $args['label'] ), // 2
				( $args['mandatory'] ) ? '<span class="wol-mandatory">' . esc_attr( $args['mandatory'] ) . '</span>' : ''
			// 3
			);

			$html = $html_label . $html;
		}

		// Check to keep old value or not
		$this->maybe_add_old_value( $args['name'], $args['value'] );

		// Add data for generating datepicker extra element and script
		if ( isset( $args['alt_field'] ) ) {
			$this->datepicker_data[ $args['id'] ]['alt_field'] = $args['alt_field'];
		}

		if ( isset( $args['script_param'] ) ) {
			$this->datepicker_data[ $args['id'] ]['script_param'] = $args['script_param'];
		}

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate Submit button
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args {
	 *                           Optional. Array or string for the submit button.
	 *
	 *      @type string  $id     The id for the submit button.
	 *      @type string  $name   The name for the submit button.
	 *      @type string  $class  One or more classes to apply to the submit button.
	 *      @type string  $value  The value to assign to the submit button
	 *      @type bool    $echo   Set if the generate html fragment of code should be echo or not
	 *                            by default it's true.
	 *
	 * @return string echo or return the code for the submit button
	 */
	public function submit_button( $args = '' ) {

		$defaults = array(
			'id'    => '',
			'name'  => '',
			'class' => '',
			'value' => '',
			'echo'  => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( '' == $args['id'] || '' == $args['name'] ) {
			$errors[] = __( 'Missing the id or the name submit button', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// Generate the block
		$html = sprintf(
			'<input type="submit" id="%1$s" name="%2$s" tabindex="%3$s" class="%4$s" value="%5$s" />',
			$args['id'], // 1
			esc_attr( $args['name'] ), // 2
			$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment, // 3
			$args['class'], // 4
			esc_attr( $args['value'] ) // 5
		);

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate hidden fields
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args {
	 *                           Optional. Array or string for hidden fields parameters.
	 *
	 *      @type array $fields  An array describing all the hidden desired fields, for each
	 *                           fields the entry key is the fields name and the value the value
	 *                           to assign to the field
	 *      @type bool   $echo   Set if the generate html fragment of code should be echo or not
	 *                           by default it's true.
	 *
	 * @return string echo or return the code for one or more hidden fields
	 */
	public function hidden_fields( $args = '' ) {

		$defaults = array(
			'fields' => array(),
			'echo'   => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct id and name
		if ( ! isset( $args['fields'] ) || empty ( $args['fields'] ) ) {
			$errors[] = __( 'No hidden fields defined', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// Set started counter for id attribute
		$hidden_id_counter = 0;
		$html              = '';

		// Generate the block
		foreach ( $args['fields'] as $name => $value ) {

			// Generate the block
			$html_field = sprintf(
				'<input type="hidden" id="hiddend_id_%1$s" name="%2$s" value="%3$s" />',
				$hidden_id_counter ++, // 1
				$name, // 2
				esc_attr( $value ) // 3
			);

			$html .= $html_field;
		}

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Generate nonce field
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string|array $args  {
	 *                            The paramenter for generating the nonce fields.
	 *
	 *      @type string  $action  the name of action for the nonce field
	 *      @type string  $name    the name for the nonce field
	 *      @type bool    $echo    Set if the generate html fragment of code should be echo or not
	 *                             by default it's true.
	 * }
	 *
	 * @return string echo or return the code for noce hidden field
	 */
	public function nonce_field( $args = '' ) {

		$defaults = array(
			'action' => - 1,
			'name'   => '',
			'echo'   => $this->default_echo,
		);

		//Parse the passed argument in an array combining with $defaults values
		$args = wp_parse_args( $args, $defaults );

		// Managing errors in parameters

		// Set errors variables
		$errors = array();

		// Check a correct name
		if ( ! isset( $args['name'] ) || empty ( $args['name'] ) ) {
			$errors[] = __( 'Missing the name of the nonce field', 'wpit-formgen' );
		}

		// If there is error format the output and echo or return it
		if ( $errors ) {
			$error_msg = implode( '<br>', $errors );

			if ( $args['echo'] ) {
				echo $error_msg;

				return FALSE;
			} else {
				return $error_msg;
			}

		}

		// Generate the block
		$html = wp_nonce_field( $args['action'], $args['name'], TRUE, FALSE );

		if ( $args['echo'] ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}

	}

	/**
	 * Return the nex tabindex value, and increment it. Usefull for adding form
	 * elements manually, keeping the correct tabindex sequence
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return integer the tabindex value to use
	 */
	public function get_new_tabindex_value() {

		$this->tabindex_counter = $this->tabindex_counter + $this->tabindex_counter_increment;

		return (int) $this->tabindex_counter;

	}

	/**
	 * Set a new tabindex value. Usefull for forcing new tabindex foif frm elements are
	 * genrated outside of the current class
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param integer $new_tabindex  The new values for the tabindex
	 *
	 * @return void
	 */
	public function set_new_tabindex_value( $new_tabindex = 0 ) {

		$this->tabindex_counter = absint( $new_tabindex );

	}

	/**
	 * If old values are stored generate a serialized values and put it in an hidden field
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param bool $echo output or return the value
	 *
	 * @return mixed tru on echo or the output value
	 */
	private function add_old_data_to_form( $echo ) {

		if ( ! $this->keep_old_values ) {
			return FALSE;
		}

		// Json encoded values are then base64 encoded to avoid any problem with entities
		$serialized_values = base64_encode( json_encode( $this->fields_old_values ) );

		// Generate the block
		$html = sprintf(
			'<input type="hidden" name="%1$s" value="%2$s" />',
			'fields_old_values', // 1
			$serialized_values // 2
		);

		if ( $echo ) {
			echo $html;

			return FALSE;
		} else {
			return $html;
		}
	}

	/**
	 * Passing the old values string base64encoded, return the array of old data
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param string $data the values of json, base64 enbcoded string from hidden field with old data
	 *
	 * @return array|bool the array of all old data or false if nothing passed
	 */
	public function get_old_data_array( $data = '' ) {

		if ( '' == $data ) {
			return FALSE;
		}

		// base64 decode value and then Json dencoded data to get the array of old values
		$old_values_array = json_decode( base64_decode( $data ), 1 );

		return $old_values_array;

	}

	/**
	 * If old values are to be stored add them to a specif array that will be
	 * returned as a specific hidden field before closing form
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param string $field_name The field name to store the value
	 * @param mixed  $old_value  The old value to add to store array
	 *
	 * @return null
	 */
	private function maybe_add_old_value( $field_name = '', $old_value = '' ) {

		if ( $this->keep_old_values ) {

			$this->fields_old_values[ $field_name ] = $old_value;

		}

		return;
	}

	/**
	 * Generate the HTML fragment for all the alternative fields for the defined datepickers
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param bool $echo output or return the value
	 *
	 * @return string the html fragmente with hidden field/s
	 */
	public function datepickers_alternate_fields( $echo ) {

		$html_fragment = '';

		foreach ( $this->datepicker_data as $id => $datepicker_datum ) {
			if ( isset( $datepicker_datum['alt_field'] ) ) {

				// Generate the hiden field for alternate datepicker field
				$html_field = sprintf(
					'<input type="hidden" id="%1$s" name="%2$s" value="%3$s" />',
					$datepicker_datum['alt_field']['id'], // 1
					$datepicker_datum['alt_field']['name'], // 2
					$datepicker_datum['alt_field']['value'] // 3
				);

				$html_fragment .= $html_field;

			}

		}

		if ( $echo ) {
			echo $html_fragment;

			return FALSE;
		} else {
			return $html_fragment;
		}


	}

	/**
	 * Generate the HTML with the javascript to initialise all the setted datepickers
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param bool $echo output or return the value
	 *
	 * @return string the html fragmente with hidden field/s
	 */
	public function create_datepickers_scripts( $echo ) {

		$html_scripts = '';

		// Open the script

		$html_scripts .= '<script>
			                 jQuery(document).ready( function ($) {;';

		$datepicker_param = array();

		foreach ( $this->datepicker_data as $id => $datepicker_datum ) {

			if ( isset( $datepicker_datum['script_param'] ) ) {

				$datepicker_param[ $id ] = array();

				foreach ( $datepicker_datum['script_param'] as $option => $value ) {
					$datepicker_param[ $id ][ $option ] = $value;
				}

				// Check if the date format is passed or set it to italian style
				$datepicker_param[ $id ]['dateFormat'] = ( isset( $datepicker_param[ $id ]['dateFormat'] ) ) ? $datepicker_param[ $id ]['dateFormat'] : 'dd/mm/yy';

				// If there is an alternate field add the params
				if ( isset( $datepicker_datum['alt_field'] ) ) {
					$datepicker_param[ $id ]['altField'] = '#' . $datepicker_datum['alt_field']['id'];
					// Check if the date format is passed or set it to msql style
					$datepicker_param[ $id ]['altFormat'] = ( isset( $datepicker_param[ $id ]['altFormat'] ) ) ? $datepicker_param[ $id ]['altFormat'] : 'yy-mm-dd';
				}
			}
		}

			$set_date = array();

			$html_script_datepicker = '';

			foreach ( $datepicker_param as $id => $values ) {

				// Generate the fragemnt for a single datepicker
				$html_script_datepicker .= sprintf(
					'$("#%1$s").datepicker({',
					$id // 1
				);

				$html_script_datepicker_param = array();

				foreach ( $values as $param => $value ) {
					if ( "setDate" == $param ) {
						$set_date[$id] = $value;
					} else {
						$html_script_datepicker_param[] = sprintf(
							'"%1$s": "%2$s"',
							$param, // 1
							$value // 2
						);
					}
				}

				$html_script_datepicker .= implode( ',', $html_script_datepicker_param );

				$html_script_datepicker .= '});';

			}

		$html_scripts .= $html_script_datepicker;

		if ( ! empty ( $set_date ) && 1 != 1 ) {
			$html_script_setdate = '';
			foreach ( $set_date as $key=>$item ) {
				$html_script_setdate .= sprintf(
					'$("#%1$s").datepicker( "setDate", "%2$s");',
					$key, // 1
					$item // 2
				);
			}

			$html_scripts .= $html_script_setdate;
		}

		$html_scripts .= '});
			</script>';

		if ( $echo ) {
			echo $html_scripts;
			return FALSE;
		} else {
			return $html_scripts;
		}

	}

}

