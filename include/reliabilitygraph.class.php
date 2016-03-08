<?php

/**
 * include/reliabilitygraph.class.php
 *
 * This class uses Graph objects from the graphp library to model
 * communication topologies and calculate end to end reliability.
 *
 * PHP version 5
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @category	default
 * @package		none
 * @author		John Lavoie
 * @copyright	2009-2016 @authors
 * @license		http://www.gnu.org/copyleft/lesser.html The GNU LESSER GENERAL PUBLIC LICENSE, Version 2.1
 */

// GraPHP library and Graphviz plugin ( apt-get install graphviz libgv-php5 )
use \Fhaculty\Graph\Graph as Graph;

class ReliabilityGraph
{
	public $graph;			// Graph object (verticies and edges
	public $paths;			// Sorted assoc array of path information
	public $data;			// Where we store all of our favorite information

	public function __construct()
	{
		$this->graph = new Graph();
	}

	public function Add_Nodes($NODES)
	{
		foreach ($NODES as $NODE)
		{
			if ( !$this->Get_Node($NODE) )
			{
				$this->graph->createVertex($NODE);
			}
		}
	}

	public function Add_Node($NODE)
	{
		if ( !$this->Get_Node($NODE) )
		{
			return $this->graph->createVertex($NODE);
		}
		return 0;
	}

	public function Add_Link($A,$B,$RELIABILITY,$UNIDIRECTIONAL = 0)
	{
		$VERTEXA = $this->graph->getVertices()->getVertexId( $A );
		$VERTEXZ = $this->graph->getVertices()->getVertexId( $B );
		if ($UNIDIRECTIONAL)
		{
			$EDGE = $VERTEXA->createEdgeTo	( $VERTEXZ );	// Unidirectional edge
		}else{
			$EDGE = $VERTEXA->createEdge	( $VERTEXZ );	// Bidirectional edge
		}
		$EDGE->setAttribute("name"					,"{$A}-{$B}");
		$EDGE->setAttribute("graphviz.color"		,"purple");
		$EDGE->setAttribute("graphviz.fontsize"		,8);
		$EDGE->setAttribute("graphviz.label"		,$RELIABILITY);
		$EDGE->setAttribute("reliability"			,$RELIABILITY);
		return $EDGE;
	}

	public function Generate_Graph_Code()
	{
		return base64_encode( serialize($this->graph) );
	}

	public function HTML_Graph()
	{
		$GRAPHVIZ_PATH = "/ajax/graphviz.png.php";
		$GRAPHCODE = $this->Generate_Graph_Code();
		return "<img src=\"{$GRAPHVIZ_PATH}?graph={$GRAPHCODE}\">";
	}

	public function Map_Paths($SOURCE,$DEST)
	{
		// Set some source node formatting for our graph
		$SRC = $this->Get_Node($SOURCE	);
		$SRC->setAttribute("graphviz.color","blue"			);
		$SRC->setAttribute("graphviz.xlabel","Source"		);
		// Set some destination node formatting for our graph
		$DST = $this->Get_Node($DEST	);
		$DST->setAttribute("graphviz.color","red"			);
		$DST->setAttribute("graphviz.xlabel","Destination"	);

		$this->paths = $this->Recursive_Find_Paths($SRC,$DST);
		uasort($this->paths,"ReliabilityGraph::Compare_Path_Reliability");	// This is how 3 likes to sort, slightly higher calculations
/*		krsort($this->paths);												// But i want to match
		uasort($this->paths,"ReliabilityGraph::Compare_Path_Length");/**/	//  how whatshisface sorted it in his thesis
	}

	/* Destination never changes,										*\
	 *	node is the current node in path, (pass it $SRC on first call)	*
	 *		path is the list of crossed edges WITH reliability!			*
	\*			visited is a list of nodes to prevent loops				*/
	public function Recursive_Find_Paths($NODE, $DEST, $PATH = array(), $VISITED = array(), $MAXDEPTH = 0 )
	{
		$PATHS = array();
		array_push( $VISITED,$NODE->getId() );							// Add this node to the list of visited to prevent loops
		if ( $NODE->getId() == $DEST->getId() )							// SUCCESS! We found a path to the destination!
		{
			return array( implode(" , ", array_keys($PATH) ) => $PATH);	// Key value pair of imploded path name with path array
		}
		foreach ($NODE->getVerticesEdgeTo() as $NEXTNODE)				// Loop through all links connected to our node
		{
			if ( in_array($NEXTNODE->getId(),$VISITED) ){ continue; }	// SKIP nodes if we have already visited them!
			$NEXTPATH = $PATH;											// COPY our path and add the next hop to it
			$AB = $NODE->getId() . "->" . $NEXTNODE->getId();			// cat a name for this link a->z
			$NEXTPATH[$AB] = $this->Get_Reliability($AB);				// Save this links reliability info for later
			if ( $MAXDEPTH && count($PATH) > $MAXDEPTH ) { continue; }	// Sanity check, dont search deeper than maxdepth hops deep...
																		// ---> rewrite this check to use count( $this->graph->getVertices()->getIds() );
			$MOREPATHS = $this->Recursive_Find_Paths(	$NEXTNODE	,	// Kick off recursion down to the next level
														$DEST		,	// A dream within a dream?
														$NEXTPATH	,	// Where next? Unstructured dreamspace?
														$VISITED	,	// <spinning top>........<wobble>.....
														$MAXDEPTH	);	// Max depth sanity check, in case we care.
			if ( count($MOREPATHS) )									// We got results from recursion,
			{
				$PATHS = array_merge($PATHS,$MOREPATHS);				// Merge recursive results into our result set
			}
		}
		return $PATHS;													// Return the recursively calculated list of paths, unsorted assoc array
	}

	public static function Compare_Path_Length($A,$B)
	{
		$CNTA = count($A);
		$CNTB = count($B);
		if ( $CNTA == $CNTB)
		{
			return 0;
		}
		return ($CNTA < $CNTB) ? -1 : 1;
	}

	public static function Compare_Path_Reliability($A,$B)
	{
		$SUMA = array_product($A);
		$SUMB = array_product($B);
		if ( $SUMA == $SUMB)
		{
			return 0;
		}
		return ($SUMA > $SUMB) ? -1 : 1;
	}

	public function HTML_Path_List()
	{
		$OUTPUT = "";
		$i = 1;
		foreach ($this->paths as $PATH)
		{
			$OUTPUT .= "Path " . $i++ . ":<br>\n";
			foreach ($PATH as $EDGE => $REL)
			{
				$OUTPUT .= "&nbsp;&nbsp;&nbsp;{$EDGE} (Reliability: {$REL})<br>\n";
			}
		}
		return $OUTPUT;
	}

	// Takes an array of paths and calculates component and terminal reliability
	public function HTML_Calculate_Reliability()
	{
		$OUTPUT = "";
		$TERMINAL = 0;
		$i = 1;
		$PATHCOUNT = count($this->paths);
		$EDGESETS = array();										// Edges that are used during path product calculations
		$OUTPUT .= <<<END
				<table class="report">
					<thead>
						<tr>
							<th>Path #</th>
							<th>Path Details</th>
							<th>Conditional Reliability</th>
							<th>Modified Unreliability</th>
							<th>Terminal Reliability Change</th>
							<th>Terminal Reliability Total</th>
						</tr>
					</thead>
					<tbody>
END;
		foreach($this->paths as $NAME => $PATH)
		{
			$CONDITIONAL = array_product( array_values($PATH) );
			$UNRELIABILITY = $this->Calculate_Unreliability($PATH,$EDGESETS,$TERMINAL,$i);
			$CHANGE = $CONDITIONAL * $UNRELIABILITY;
			$TERMINAL += $CHANGE;
			array_push( $EDGESETS , array( $NAME => $PATH ) );		// Add this paths edges to calculate unreliability

			$FORMAT["condrel"]		= $this->Format_Percent($CONDITIONAL);
			$FORMAT["unrel"]		= $this->Format_Percent($UNRELIABILITY);
			$FORMAT["termrelchg"]	= $this->Format_Percent($CHANGE);
			$FORMAT["termrel"]		= $this->Format_Percent($TERMINAL);
			$ROWCLASS = "row" . ( ($i % 2) + 1 );
			$OUTPUT .= <<<END
						<tr class={$ROWCLASS}>
							<td>({$i}/{$PATHCOUNT})</td>
							<td>[{$NAME}]</td>
							<td>{$FORMAT["condrel"]}</td>
							<td>{$FORMAT["unrel"]}</td>
							<td>{$FORMAT["termrelchg"]}</td>
							<td>{$FORMAT["termrel"]}</td>
						</tr>
END;
			$i++;
		}
		$OUTPUT .= <<<END
					</tbody>
					<caption style="color: red; background: #36086F;">Terminal Reliability: {$FORMAT["termrel"]}</caption>
				</table>
END;
		return $OUTPUT;
	}

	public function Calculate_Unreliability($PATH,$EDGESETS,$TERMINAL,$i)
	{
		$UNREL = 1;
		if ( $i == 1 ) { return $UNREL; }		// The first iteration we are completely reliable.
		foreach ($EDGESETS as $EDGESET)			// And subsequent iterations, we are UNreliable by
		{										//  the product of previously used paths
			$EDGESET = reset($EDGESET);			//    NOT present in the current path!
			foreach ($PATH as $EDGE => $REL)	// Unreliability is the INVERSE of reliable: U=1-R
			{
				if ( isset($EDGESET[$EDGE]) ) { unset($EDGESET[$EDGE]); }
			}
			$UNREL *= ( 1 - array_product(array_values($EDGESET)) );
		}
		return $UNREL;
	}

	public function Format_Percent($FLOAT)
	{
		return rtrim(rtrim( number_format($FLOAT * 100 , 8 ) ,"0"),".") . "%";
	}

	function Get_Node($NODE)
	{
		if ( $this->graph->getVertices()->hasVertexId($NODE) )
		{
			return $this->graph->getVertices()->getVertexId($NODE);
		}
		return 0;
	}

	function Find_Edge($A,$B)
	{
		$ANODE = $this->Get_Node($A);
		$BNODE = $this->Get_Node($B);

		foreach ($ANODE->getEdges() as $EDGE)
		{
			if ( $EDGE->isConnection($ANODE,$BNODE) )
			{
				return $EDGE;
			}
		}
		return 0;
	}

	function Get_Reliability($LINK)
	{
		if ( preg_match("/(.*)-[>]?(.*)/",$LINK,$MATCHES) )
		{
			$EDGE = $this->Find_Edge($MATCHES[1],$MATCHES[2]);
			return $EDGE->getAttribute("reliability");
		}else{
			die( "Unrecoverable error, could not match pattern:\n" . var_dump($MATCHES) );
		}
	}

	function getAttribute( $KEY )
	{
		return $this->graph->getAttribute($KEY);
	}

	function setAttribute( $KEY , $VALUE )
	{
		return $this->graph->setAttribute($KEY,$VALUE);
	}

}
