<?php
/*************PAGAMENTO DIGITAL*************
 *  Pagamento Digital  V5 09/10/2011     *
 *  Modulo de integracao TomatoCarto 1.1.X *
 *  Wics Tecnologia                        *
 *  www.wics.com.br                        *
 *  contato cleber@wics.com.br             * 
 *  GNU General Public License is a free . *
 ******************************************* 
***/
  class osC_Payment_pgdwics extends osC_Payment_Admin {

/**
 * The administrative title of the payment module
 *
 * @var string
 * @access private
 */

    var $_title;
/**
 * The code of the payment module
 *
 * @var string
 * @access private
 */

    var $_code = 'pgdwics';

/**
 * The developers name
 *
 * @var string
 * @access private
 */

    var $_author_name = 'Wics Tecnologia';

/**
 * The developers address
 *
 * @var string
 * @access private
 */

  var $_author_www = 'http://www.wics.com.br';

/**
 * The status of the module
 *
 * @var boolean
 * @access private
 */

    var $_status = false;

/**
 * Constructor
 */

    function osC_Payment_pgdwics() {
      global $osC_Language;
      
	  $this->_title = $osC_Language->get('payment_pgdwics_title');
	  $this->_description = $osC_Language->get('payment_pgdwics_description');
	  $this->_method_title = $osC_Language->get('payment_pgdwics_method_title');
      $this->_status = (defined('MODULE_PAYMENT_PGDWICS_STATUS') && (MODULE_PAYMENT_PGDWICS_STATUS == '1') ? true : false);
      $this->_sort_order = (defined('MODULE_PAYMENT_PGDWICS_SORT_ORDER') ? MODULE_PAYMENT_PGDWICS_SORT_ORDER : null);
    }

/**
 * Checks to see if the module has been installed
 *
 * @access public
 * @return boolean
 */

    function isInstalled() {
      return (bool)defined('MODULE_PAYMENT_PGDWICS_STATUS');
    }

/**
 * Installs the module
 *
 * @access public
 * @see osC_Payment_Admin::install()
 */

    function install() {
      global $osC_Database;

      parent::install();

      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('* HABILITAR O MODULO', 'MODULE_PAYMENT_PGDWICS_STATUS', '1', '<br/>Deseja aceitar o Pagamento Digital como forma depagamento? <br/>', '6', '3', 'osc_cfg_set_boolean_value(array(1, -1))', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('<br/>* COD. ID LOJA (codigo da loja ou email)', 'MODULE_PAYMENT_PGDWICS_ID', '658926', '<br/>Essa configuracao fica na sua conta no Pagamento Digital, abaixo exemplo de loja modelo teste <br/>', '6', '4', now())");
	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('<br/>* COD. TOKEN (chave de acesso para retorno)', 'MODULE_PAYMENT_PGDWICS_CHAVE', '56206C8663094A6494AA', '<br/>Essa configuracao fica na sua conta no Pagamento Digital <br/>', '6', '4', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('<br/>* FUNCIONAMENTO DO MODULO', 'MODULE_PAYMENT_PGDWICS_GATEWAY_MODE', 'Producao', '<br/>Use Producao somente quando tiver tudo certinho!, para nao encher sua conta de pedidos inexistentes. <br/>', '6', '7', 'osc_cfg_set_boolean_value(array(\'Producao\', \'Teste\'))', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Ordem do modulo de pagamento.', 'MODULE_PAYMENT_PGDWICS_SORT_ORDER', '0', '0 e o primeiro da ordem de metodos de pagamento caso tenha mais de um. <br/>', '6', '0', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Regiao onde usar o modulo', 'MODULE_PAYMENT_PGDWICS_ZONE', '0', 'Selecione Brasil, Caso nao tenha voce devera criar uma regiao em: iniciar/definicao/grupos de zonas  <br/>', '6', '2', 'osc_cfg_use_get_zone_class_title', 'osc_cfg_set_zone_classes_pull_down_menu', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Pedidos inicialmente usando este modulo estao:', 'MODULE_PAYMENT_PGDWICS_ORDER_STATUS_ID', '" . ORDERS_STATUS_PREPARING . "',  'E recomendado deixar  processando pois o propio pagamento digital atualiza o status do pedido automaticamente. <br/>', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");
	  $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_description,date_added) values ('<br/>* Modulo adaptado por Wics Web Designer & Hosting:', 'MODULE_PAYMENT_PGDWICS_SOBRE', ' Contato e Duvidas: suporte@wics.com.br  <br/><br/> Site: http://www.wics.com.br :.) <br/>', now())");
    }

/**MODULE_PAYMENT_PGDWICS_URL_RETORNO
MODULE_PAYMENT_PGDWICS_CHAVE
MODULE_PAYMENT_PGDWICS_TIPO_INTEGRACAO
 * Return the configuration parameter keys in an array
 *
 * @access public
 * @return array
 */

    function getKeys() {
      if (!isset($this->_keys)) {
        $this->_keys = array('MODULE_PAYMENT_PGDWICS_STATUS',
                             'MODULE_PAYMENT_PGDWICS_ID',
							 'MODULE_PAYMENT_PGDWICS_CHAVE',
                             'MODULE_PAYMENT_PGDWICS_GATEWAY_MODE',
                             'MODULE_PAYMENT_PGDWICS_ZONE',
                             'MODULE_PAYMENT_PGDWICS_ORDER_STATUS_ID',
                             'MODULE_PAYMENT_PGDWICS_SORT_ORDER',
							 'MODULE_PAYMENT_PGDWICS_SOBRE');
      }

      return $this->_keys;
    }
  }
?>