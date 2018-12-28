<?php
class FatturaPA {
	
	protected $_node = ['FatturaElettronicaHeader' => [], 'FatturaElettronicaBody' => []];
	
	/**
	 * Imposta il formato (utilizzare constanti definite in FatturaPA_Formato)
	 * https://github.com/s2software/fatturapa/wiki/Costanti#formato-trasmissione
	 * @param string $formato (default: FPR12 = Privati)
	 */
	public function __construct($formato = 'FPR12')
	{
		$this->_set_node('FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione', $formato);
	}
	
	/**
	 * Imposta dati trasmittente (es.: azienda o commercialista) (opzionale: copia dati mittente)
	 * @param array $data
	 */
	public function set_trasmiettente($data)
	{
		$map = array(
				'paese' => 'FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese',
				'piva' => 'FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice',
		);
		$this->_fill_node($map, $data);
	}
	
	/**
	 * Imposta il mittente/fornitore della fattura
	 * @param array $data
	 * - piva_paese: opzionale (copia dato paese)
	 * - regimefisc: https://github.com/s2software/fatturapa/wiki/Costanti#regime-fiscale
	 */
	public function set_mittente($data)
	{
		$node = &$this->_set_node('FatturaElettronicaHeader/CedentePrestatore', []);
		$this->_set_anagr($data, $node);
		
		// default mittente
		$this->_set_defaults([
				// trasmiettente - default: copia dati del mittente
				'FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese' =>
					$this->_get_node('FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdPaese'),
				'FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice' =>
					$this->_get_node('FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/IdFiscaleIVA/IdCodice'),
				// regimefisc è opzionale: default: RF01 = ordinario
				'FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici/RegimeFiscale' =>
					'RF01',
		]);
	}
	
	/**
	 * Imposta il destinatario/cliente della fattura
	 * @param array $data
	 * - piva_paese: opzionale (copia dato paese)
	 * - sdi_codice / sdi_pec sono alternativi
	 */
	public function set_destinatario($data)
	{
		$node = &$this->_set_node('FatturaElettronicaHeader/CessionarioCommittente', []);
		$this->_set_anagr($data, $node);
		
		$map = array(
				'sdi_codice' => 'FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario',
				'sdi_pec' => 'FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario',
		);
		$this->_fill_node($map, $data);
		
		// default destinatario
		$this->_set_defaults([
				// set_destinatario > sdi_codice - default: 0000000
				'FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario' =>
					'0000000',
		]);
	}
	
	/**
	 * 
	 * @param array $data
	 * @param string $type ('mittente'/'destinatario')
	 */
	protected function _set_anagr($data, &$node)
	{
		$map = array(
				'piva_paese' => 'DatiAnagrafici/IdFiscaleIVA/IdPaese',
				'piva' => 'DatiAnagrafici/IdFiscaleIVA/IdCodice',
				'codfisc' => 'DatiAnagrafici/CodiceFiscale',
				'ragsoc' => 'DatiAnagrafici/Anagrafica/Denominazione',
				'regimefisc' => 'DatiAnagrafici/RegimeFiscale',
				'indirizzo' => 'Sede/Indirizzo',
				'cap' => 'Sede/CAP',
				'comune' => 'Sede/Comune',
				'prov' => 'Sede/Provincia',
				'paese' => 'Sede/Nazione',
		);
		$this->_fill_node($map, $data, $node);
		
		// se è una partita iva
		if (isset($data['piva']))
		{
			$this->_set_defaults([
					// paese p.iva: default impostato quello della sede
					'DatiAnagrafici/IdFiscaleIVA/IdPaese' =>
						$this->_get_node('Sede/Nazione', $node)
			], $node);
		}
	}
	
	/**
	 * Imposta i dati di intestazione della fattura
	 * @param array $data
	 * - tipodoc: https://github.com/s2software/fatturapa/wiki/Costanti#tipo-documento (default: TD01 = Fattura)
	 * - progressivo: impostato di default allo stesso valore del campo 'numero'
	 * - causale: opzionale (max 200 caratteri)
	 */
	public function set_intestazione($data)
	{
		$map = array(
				'tipodoc' => 'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento',
				'valuta' => 'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Divisa',
				'data' => 'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Data',
				'numero' => 'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero',
				'progressivo' => 'FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio',
				'causale' => 'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Causale',
		);
		$this->_fill_node($map, $data);
		
		// imposta default
		$this->_set_defaults([
				// tipodoc - default: TD01 = Fattura
				'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/TipoDocumento' =>
					'TD01',
				// valuta - default: EUR
				'FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Divisa' =>
					'EUR',
				// progressivo - default: copia numero
				'FatturaElettronicaHeader/DatiTrasmissione/ProgressivoInvio' =>
					$this->_get_node('FatturaElettronicaBody/DatiGenerali/DatiGeneraliDocumento/Numero'),
		]);
	}
	
	/**
	 * Aggiunge una riga di dettaglio
	 * @param array $data
	 */
	public function add_riga($data)
	{
		$path = 'FatturaElettronicaBody/DatiBeniServizi/DettaglioLinee';
		$map = array(
				'num' => 'NumeroLinea',
				'descrizione' => 'Descrizione',
				'prezzo' => 'PrezzoUnitario',
				'qta' => 'Quantita',
				'importo' => 'PrezzoTotale',
				'perciva' => 'AliquotaIVA',
		);
		$node = [];
		$this->_fill_node($map, $data, $node);
		$this->_add_node($path, $node);
	}
	
	/**
	 * Imposta i dati relativi al totale fattura
	 * @param array $data
	 * - esigiva: https://github.com/s2software/fatturapa/wiki/Costanti#esigibilita-iva
	 */
	public function set_totali($data)
	{
		$map = array(
				'importo' => 'FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/ImponibileImporto',
				'perciva' => 'FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/AliquotaIVA',
				'iva' => 'FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/Imposta',
				'esigiva' => 'FatturaElettronicaBody/DatiBeniServizi/DatiRiepilogo/EsigibilitaIVA',
		);
		$this->_fill_node($map, $data);
	}
	
	/**
	 * Imposta dati pagamento (opzionale)
	 * @param array $data
	 * @param array $mods Modalità (possibile più di una)
	 * - condizioni: https://github.com/s2software/fatturapa/wiki/Costanti#condizioni-pagamento (default: TP02 = completo)
	 * - $modes - modalita:  https://github.com/s2software/fatturapa/wiki/Costanti#modalit%C3%A0-pagamento
	 */
	public function set_pagamento($data, $modes)
	{
		$map = array(
				'condizioni' => 'FatturaElettronicaBody/DatiPagamento/CondizioniPagamento'
		);
		$this->_fill_node($map, $data);
		
		$path = 'FatturaElettronicaBody/DatiPagamento/DettaglioPagamento';
		$map = array(
				'modalita' => 'ModalitaPagamento',
				'totale' => 'ImportoPagamento',
				'scadenza' => 'DataScadenzaPagamento',
				'iban' => 'IBAN',
		);
		if ($this->_is_assoc($modes))	// assoc array to array of assoc array
		{
			$modes = [$modes];
		}
		foreach ($modes as $mode)
		{
			$node = [];
			$this->_fill_node($map, $mode, $node);
			$this->_add_node($path, $node);
		}
	}
	
	/**
	 * Imposta liberamente valore nodo dall'esterno
	 * @param string $path
	 * @param mixed $data
	 * @return mixed
	 */
	public function &set_node($path, $value)
	{
		return $this->_set_node($path, $value);
	}
	
	/**
	 * Aggiunge liberamento nodo dall'esterno
	 * @param string $path
	 * @param mixed $data
	 * @return mixed
	 */
	public function &add_node($path, $data)
	{
		return $this->_add_node($path, $data);
	}
	
	/**
	 * Ottiene nodo dall'esterno
	 * @param string $path
	 * @return mixed
	 */
	public function &get_node($path = '')
	{
		if (!$path)
			return $this->_node;
		return $this->_get_node($path);
	}
	
	/**
	 * Ottiene l'XML completo della fattura elettronica
	 */
	public function get_xml()
	{
		$formato = $this->get_node('FatturaElettronicaHeader/DatiTrasmissione/FormatoTrasmissione');
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
				'<p:FatturaElettronica versione="'.$formato.'" xmlns:ds="http://www.w3.org/2000/09/xmldsig#"'."\n".
				'xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"'."\n".
				'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\n".
				'xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2'.
				' http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">'."\n";
		
		$xml .= $this->_to_xml();
		
		$xml .= '</p:FatturaElettronica>';
		return $xml;
	}
	
	/**
	 * Produce XML da applicare nel template base
	 */
	public function to_xml()
	{
		return $this->_to_xml();
	}
	
	/**
	 * Nome file da generare in base ai dati passati (senza estensione xml)
	 * https://www.fatturapa.gov.it/export/fatturazione/it/c-11.htm
	 * @param string $progr Solo alfanumerici, massimo 5 caratteri
	 * @return string
	 */
	public function filename($progr)
	{
		$paese = $this->_get_node('FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdPaese');
		$piva = $this->_get_node('FatturaElettronicaHeader/DatiTrasmissione/IdTrasmittente/IdCodice');
		return $paese.$piva.'_'.$progr.'.xml';
	}
	
	/**
	 * Produce XML
	 * @param array $node
	 * @param string $xml
	 */
	protected function _to_xml($node = NULL, $level = 1)
	{
		if ($node === NULL)
			$node = $this->_node;
		
		$xml = '';
		foreach ($node as $name => $child)
		{
			$nodes = [$child];
			if (is_array($child) && !$this->_is_assoc($child))	// più nodi con lo stesso nome (es.: DettaglioLinee)
			{
				$nodes = $child;
			}
			$pad = str_repeat('  ', $level);
			foreach ($nodes as $i => $sub)
			{
				$xml .= $pad."<{$name}>";
				if (is_array($sub))	// l'albero prosegue
				{
					$xml .= "\n";
					$xml .= $this->_to_xml($sub, $level+1);
					$xml .= $pad;
				}
				else	// è un nodo finale: qui ho il valore
				{
					$xml .= htmlspecialchars($sub);
					
				}
				$xml .= "</{$name}>"."\n";
			}
		}
		return $xml;
	}
	
	/**
	 * Applica default
	 * @param array $defaults
	 */
	protected function _set_defaults($defaults, &$node = NULL)
	{
		if ($node === NULL)
			$node = &$this->_node;
		
		foreach ($defaults as $path => $value)
		{
			if ($this->_get_node($path, $node) === NULL)	// se il nodo non è impostato
			{
				$this->_set_node($path, $value, $node);
			}
		}
	}
	
	/**
	 * Compila la struttura con $data guardando la $map
	 * @param array $map
	 * @param array $data
	 * @param array $node (optional)
	 * @return mixed
	 */
	protected function &_fill_node($map, $data, &$node = NULL)
	{
		if ($node === NULL)
			$node = &$this->_node;
		
		foreach ($map as $field => $path)
		{
			if (isset($data[$field]))
			{
				$this->_set_node($path, $data[$field], $node);
			}
		}
		return $node;
	}
	
	/**
	 * Aggiunge nodo alla struttura
	 * @param mixed $path Percorso nodo
	 * @param mixed $value Dato da scrivere nel nodo
	 * @param array $node Base array nodi
	 * @return mixed
	 */
	protected function &_set_node($path, $value, &$node = NULL)
	{
		if (!$path)
			return;
		
		if (!is_array($path))
			$path = explode('/', $path);
		
		if ($node === NULL)
			$node = &$this->_node;
		
		$null = NULL;
		$name = $path[0];	// nodo da inizializzare se non presente
		$child_path = array_slice($path, 1);	// percorso successivo
		if ($child_path)	// se il percorso continua, richiama ricorsivamente questa funzione
		{
			if (!isset($node[$name]))	// inizializza il nodo se non esiste
				$node[$name] = [];
			return $this->_set_node($child_path, $value, $node[$name]);
		}
		else	// altrimenti, se sono arrivato al nodo finale, scrivo il valore
		{
			$node[$name] = $value;
			return $node[$name];
		}
		return $null;
	}
	
	/**
	 * Ritorna nodo dalla struttura
	 * @param mixed $path
	 * @param array $node Base array nodi
	 * @return mixed
	 */
	protected function &_get_node($path, &$node = NULL)
	{
		if (!$path)
			return NULL;
		
		if (!is_array($path))
			$path = explode('/', $path);
		
		if ($node === NULL)
			$node = &$this->_node;
		
		$null = NULL;
		$name = $path[0];
		$child_path = array_slice($path, 1);
		if (isset($node[$name]))
		{
			if ($child_path)	// se il percorso continua, richiama ricorsivamente questa funzione
			{
				return $this->_get_node($child_path, $node[$name]);
			}
			else
			{
				return $node[$name];
			}
		}
		return $null;
	}
	
	/**
	 * Aggiunge nodo (caso più nodi con lo stesso nome)
	 * @param string $path
	 * @param mixed $data
	 * @return mixed
	 */
	protected function &_add_node($path, $data)
	{
		$node = &$this->_get_node($path);
		if (!$node)
		{
			$node = &$this->_set_node($path, []);
		}
		$node[] = $data;
		return $node[count($node)-1];
	}
	
	/**
	 * Is associative array?
	 * @param array $arr
	 * @return boolean
	 */
	protected function _is_assoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	
	/**
	 * Format decimal
	 * @param float $value
	 */
	static function dec($value)
	{
		return number_format($value, 2, '.', '');
	}
}