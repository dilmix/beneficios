<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Albert Dilme  
 * Copyright (C) 2017  Francesc Pineda Segarra  shawe.ewahs@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


class beneficios extends fs_controller {

   // Almacena un array de documentos de venta
   public $documentos;
   //Almacena la tabla donde se encuentran los $documentos
   public $table;
   // Acumula el neto de documentos de venta
   public $total_neto;
   // Acumula el precio de coste de los articulos que hay del array de documentos de venta
   public $total_coste;
   // Diferencia entre total_neto y total_coste
   public $total_beneficio;
   public $test;

   /**
    * Constructor del controlador (heredado de fs_controller)
    * 
    * Crea una entrada 'Beneficios' dentro del menú 'informes'
    */
   public function __construct() {
      /* Como no necesita aparecer en el menú añadimos los parametros opcionales FALSE */
      parent::__construct(__CLASS__, 'Beneficios', 'informes', FALSE, FALSE);
   }

   /**
    * Lógica privada principal del controlador (heredado de fs_controller)
    * 
    * Tu código PHP lo pondrás aquí
    */
   protected function private_core() {
      $this->share_extension();

      $this->test = "";
      $this->documentos = filter_input(INPUT_POST, 'docs', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
      if (!empty($this->documentos)) {
         $this->table=$this->table($this->documentos);
         $this->total_neto = $this->totalneto($this->documentos);
         $this->total_coste = $this->totalcoste($this->documentos);
         $this->total_beneficio = $this->beneficio($this->total_neto, $this->total_coste);


         
         $this->test = json_encode($this->documentos);
      } else {
         $this->test = "No se han recibido datos";
      }

       //echo($this->total_coste);
   }

   /**
    * Extensión para integrarse en otras páginas (heredado de fs_controller)
    */
   private function share_extension() {

       $fsext = new fs_extension();
       $fsext->name = 'beneficios_facturas';
       $fsext->from = __CLASS__;
       $fsext->to = 'ventas_facturas';
       $fsext->type = 'head';
       $fsext->text = ' <script type="text/javascript" src="plugins/beneficios/view/js/beneficios.js"></script>';
       $fsext->save();

       $fsext = new fs_extension();
       $fsext->name = 'beneficios_albaranes';
       $fsext->from = __CLASS__;
       $fsext->to = 'ventas_albaranes';
       $fsext->type = 'head';
       $fsext->text = ' <script type="text/javascript" src="plugins/beneficios/view/js/beneficios.js"></script>';
       $fsext->save();

       $fsext = new fs_extension();
       $fsext->name = 'beneficios_pedidos';
       $fsext->from = __CLASS__;
       $fsext->to = 'ventas_pedidos';
       $fsext->type = 'head';
       $fsext->text = ' <script type="text/javascript" src="plugins/beneficios/view/js/beneficios.js"></script>';
       $fsext->save();

       $fsext = new fs_extension();
       $fsext->name = 'beneficios_presupuestos';
       $fsext->from = __CLASS__;
       $fsext->to = 'ventas_presupuestos';
       $fsext->type = 'head';
       $fsext->text = ' <script type="text/javascript" src="plugins/beneficios/view/js/beneficios.js"></script>';
       $fsext->save();

       $fsext = new fs_extension();
       $fsext->name = 'beneficios_factura';
       $fsext->from = __CLASS__;
       $fsext->to = 'ventas_factura';
       $fsext->type = 'head';
       $fsext->text = ' <script type="text/javascript" src="plugins/beneficios/view/js/beneficios.js"></script>';
       $fsext->save();

      /* $fsext = new fs_extension();
       $fsext->name = 'beneficios_factura2';
       $fsext->from = __CLASS__;
       $fsext->to = 'ventas_factura';
       $fsext->type = 'head';
       $fsext->text = ' <script type="text/javascript" src="plugins/beneficios/view/js/beneficios_documento.js"></script>';
       $fsext->save();*/

   }

    /**
     * Devuelve la tabla donde están los documentos
     *
     * @param $array_documentos
     * @return string
     */
    public function table($array_documentos)
    {
        $value=array_shift($array_documentos);
        $sql="SELECT idfactura FROM facturascli WHERE codigo='$value'";
        $data=$this->db->select("$sql");
        if($data)
        {
            $data='facturascli';
        }
        else
        {
            $sql="SELECT idalbaran FROM albaranescli WHERE codigo='$value'";
            $data=$this->db->select("$sql");
            if ($data)
            {
                $data='albaranescli';
            }
            else
            {
                $sql="SELECT idpedido FROM pedidoscli WHERE codigo='$value'";
                $data=$this->db->select("$sql");
                if ($data)
                {
                    $data='pedidoscli';
                }
                else $data='presupuestoscli';
            }

        }

        return $data;
    }

    /**
    * Devuelve el importe total neto del array de documentos recibido
    * 
    * @param type $array_documentos
    * @return double
    */
   public function totalneto($array_documentos) {
      $totalneto = 0;

      // Buscamos los netos de las facturas recibidas en $array_documentos
      $sql = "SELECT neto FROM ".$this->table." WHERE codigo IN ('" . join("','", $array_documentos) . "')";
      $data = $this->db->select("$sql");

      foreach ($data as $d) {
         $totalneto = $totalneto + $d['neto'];
      }

      return $totalneto;
   }

   /**
    * Devuelve el importe total de coste del array de documentos recibido
    * 
    * @param type $array_documentos
    * @return double
    */
   public function totalcoste($array_documentos) {
      $totalcoste = 0;

       switch ($this->table)
       {
           case 'facturascli':
               $doc='factura';
               break;
           case 'albaranescli':
               $doc='albaran';
               break;
           case 'pedidoscli';
               $doc='pedido';
               break;
           case 'presupuestoscli':
               $doc='presupuesto';
               break;

       }

     // Buscamos la referencia, preciocoste, cantidad y pvptotal de las facturas recibidas en $array_facturas
// Alternativa 1
/*
      $sql = "SELECT articulos.referencia, articulos.preciocoste, lineasfacturascli.cantidad, lineasfacturascli.pvptotal ";
      $sql .= "FROM articulos ";
      $sql .= "LEFT JOIN lineasfacturascli ON lineasfacturascli.referencia = articulos.referencia ";
      $sql .= "LEFT JOIN facturascli ON lineasfacturascli.idfactura = facturascli.idfactura ";
      $sql .= "WHERE facturascli.codigo IN ('" . join("','", $array_facturas) . "')";
*/
// Alternativa 2

      $sql = "SELECT articulos.referencia, articulos.preciocoste, lineas".$this->table.".cantidad, lineas".$this->table.".pvptotal ";
      $sql .= "FROM articulos, ".$this->table." ";
      $sql .= "LEFT JOIN lineas".$this->table." ON lineas".$this->table.".id".$doc." = ".$this->table.".id".$doc." ";
      $sql .= "WHERE lineas".$this->table.".referencia = articulos.referencia AND ".$this->table.".codigo IN ('" . join("','", $array_documentos) . "')";

      
      $data = $this->db->select("$sql");
      if ($data) {
         foreach ($data as $d) {
            $preciocoste = $d["preciocoste"];
            $cantidad = $d["cantidad"];
            $costeporcantidad = $preciocoste * $cantidad;
            $totalcoste = $totalcoste + $costeporcantidad;
         }
      }

      return $totalcoste;
   }

   /**
    * Devuelve el cálculo del beneficio
    * 
    * @param double $total_neto
    * @param double $total_coste
    * @return double
    */
   public function beneficio($total_neto, $total_coste) {
      return $total_neto - $total_coste;
   }

}
