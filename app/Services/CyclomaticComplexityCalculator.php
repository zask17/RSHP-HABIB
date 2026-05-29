<?php

namespace App\Services;

/**
 * CyclomaticComplexityCalculator — Menghitung Cyclomatic Complexity V(G)
 * menggunakan tiga metode McCabe:
 * 
 * 1. Metode Edge-Node:     V(G) = E - N + 2
 * 2. Metode Predicate Node: V(G) = P + 1
 * 3. Metode Region:         V(G) = R (jumlah region dalam CFG)
 */
class CyclomaticComplexityCalculator
{
    /**
     * Hitung Cyclomatic Complexity menggunakan Metode Edge dan Node.
     * 
     * Rumus: V(G) = E - N + 2
     * 
     * @param int $edges   Jumlah edge (sisi) pada Control Flow Graph (CFG).
     * @param int $nodes   Jumlah node (simpul) pada Control Flow Graph (CFG).
     * @return int         Nilai Cyclomatic Complexity V(G).
     */
    public function calculateByEdgeNode(int $edges, int $nodes): int
    {
        return $edges - $nodes + 2;
    }

    /**
     * Hitung Cyclomatic Complexity menggunakan Metode Predicate Node.
     * 
     * Rumus: V(G) = P + 1
     * 
     * Predicate node adalah node yang memiliki 2 atau lebih cabang,
     * seperti: if, elseif, for, foreach, while, case.
     * 
     * @param int $predicateNodes  Jumlah predicate node (P).
     * @return int                 Nilai Cyclomatic Complexity V(G).
     */
    public function calculateByPredicateNode(int $predicateNodes): int
    {
        return $predicateNodes + 1;
    }

    /**
     * Hitung Cyclomatic Complexity menggunakan Metode Region.
     * 
     * Region adalah jumlah area tertutup (bounded region) dalam 
     * Control Flow Graph (CFG) ditambah 1 area luar (unbounded region).
     * 
     * V(G) = R (jumlah region dalam CFG)
     * 
     * @param int $regions  Jumlah region dalam Control Flow Graph.
     * @return int          Nilai Cyclomatic Complexity V(G).
     */
    public function calculateByRegion(int $regions): int
    {
        return $regions;
    }

    /**
     * Hitung Cyclomatic Complexity menggunakan ketiga metode sekaligus.
     * 
     * @param int|null $edges          Jumlah edge (E), null jika tidak dihitung.
     * @param int|null $nodes          Jumlah node (N), null jika tidak dihitung.
     * @param int|null $predicateNodes Jumlah predicate node (P), null jika tidak dihitung.
     * @param int|null $regions        Jumlah region (R), null jika tidak dihitung.
     * @return array                   Array asosiatif berisi hasil perhitungan.
     */
    public function calculateAll(
        ?int $edges = null,
        ?int $nodes = null,
        ?int $predicateNodes = null,
        ?int $regions = null
    ): array {
        $results = [];

        if ($edges !== null && $nodes !== null) {
            $results['edge_node'] = [
                'method' => 'V(G) = E - N + 2',
                'edges' => $edges,
                'nodes' => $nodes,
                'formula' => "V(G) = {$edges} - {$nodes} + 2",
                'result' => $this->calculateByEdgeNode($edges, $nodes),
            ];
        }

        if ($predicateNodes !== null) {
            $results['predicate_node'] = [
                'method' => 'V(G) = P + 1',
                'predicate_nodes' => $predicateNodes,
                'formula' => "V(G) = {$predicateNodes} + 1",
                'result' => $this->calculateByPredicateNode($predicateNodes),
            ];
        }

        if ($regions !== null) {
            $results['region'] = [
                'method' => 'V(G) = R',
                'regions' => $regions,
                'formula' => "V(G) = {$regions}",
                'result' => $this->calculateByRegion($regions),
            ];
        }

        return $results;
    }

    /**
     * Hitung Cyclomatic Complexity dari source code PHP dengan menganalisis
     * token untuk menghitung jumlah predicate node.
     * 
     * Predicate node meliputi: if, elseif, for, foreach, while, case.
     * 
     * @param string $sourceCode Source code PHP sebagai string.
     * @return array             Array hasil analisis dengan detail predicate node dan V(G).
     */
    public function calculateFromSourceCode(string $sourceCode): array
    {
        $tokens = token_get_all($sourceCode);
        $predicateNodes = [];
        $count = 0;
        $lineNumbers = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $tokenName = $token[0];
                $tokenValue = $token[1];
                $line = $token[2];

                switch ($tokenName) {
                    case T_IF:
                        $count++;
                        $predicateNodes[] = 'if';
                        $lineNumbers[] = $line;
                        break;
                    case T_ELSEIF:
                        $count++;
                        $predicateNodes[] = 'elseif';
                        $lineNumbers[] = $line;
                        break;
                    case T_FOR:
                        $count++;
                        $predicateNodes[] = 'for';
                        $lineNumbers[] = $line;
                        break;
                    case T_FOREACH:
                        $count++;
                        $predicateNodes[] = 'foreach';
                        $lineNumbers[] = $line;
                        break;
                    case T_WHILE:
                        $count++;
                        $predicateNodes[] = 'while';
                        $lineNumbers[] = $line;
                        break;
                    case T_CASE:
                        $count++;
                        $predicateNodes[] = 'case';
                        $lineNumbers[] = $line;
                        break;
                }
            }
        }

        $vG = $this->calculateByPredicateNode($count);

        return [
            'total_predicate_nodes' => $count,
            'predicate_nodes' => $predicateNodes,
            'line_numbers' => $lineNumbers,
            'v_g' => $vG,
            'formula' => "V(G) = {$count} + 1 = {$vG}",
            'interpretation' => $this->interpretComplexity($vG),
        ];
    }

    /**
     * Hitung kompleksitas dari sebuah method/function dalam file PHP.
     * 
     * Mengekstrak body function/method dan menganalisis predicate nodes
     * di dalamnya untuk menghitung Cyclomatic Complexity.
     * 
     * @param string $filePath    Path ke file PHP.
     * @param string $functionName Nama fungsi/method (opsional). Jika kosong, analisis seluruh file.
     * @return array              Array hasil analisis.
     */
    public function analyzeFunction(string $filePath, string $functionName = ''): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File tidak ditemukan: {$filePath}");
        }

        $sourceCode = file_get_contents($filePath);

        if (!empty($functionName)) {
            // Ekstrak fungsi tertentu
            $pattern = '/function\s+' . preg_quote($functionName, '/') . '\s*\([^)]*\)\s*(:\s*[^{]+)?\{/';
            if (!preg_match($pattern, $sourceCode, $matches, PREG_OFFSET_CAPTURE)) {
                throw new \InvalidArgumentException("Fungsi '{$functionName}' tidak ditemukan dalam file.");
            }

            $startPos = $matches[0][1];
            $braceStart = strpos($sourceCode, '{', $startPos);
            $braceCount = 0;
            $functionCode = '';

            for ($i = $braceStart; $i < strlen($sourceCode); $i++) {
                $char = $sourceCode[$i];
                $functionCode .= $char;
                if ($char === '{') {
                    $braceCount++;
                } elseif ($char === '}') {
                    $braceCount--;
                }
                if ($braceCount === 0) {
                    break;
                }
            }

            $result = $this->calculateFromSourceCode("<?php\n" . $functionCode);
            $result['function'] = $functionName;
            $result['file'] = $filePath;
            $result['code_snippet'] = $functionCode;
        } else {
            $result = $this->calculateFromSourceCode($sourceCode);
            $result['file'] = $filePath;
        }

        return $result;
    }

    /**
     * Interpretasi nilai Cyclomatic Complexity.
     * 
     * @param int $vG Nilai Cyclomatic Complexity.
     * @return string Interpretasi risiko.
     */
    public function interpretComplexity(int $vG): string
    {
        return match (true) {
            $vG <= 10  => '🐤 Rendah — Program sederhana, risiko rendah, mudah diuji.',
            $vG <= 20  => '⚠️  Sedang — Kompleksitas moderat, perlu perhatian lebih dalam pengujian.',
            $vG <= 50  => '🔴 Tinggi — Program kompleks, risiko tinggi, sulit diuji dan dipelihara.',
            default    => '💀 Sangat Tinggi — Program sangat kompleks, sangat berisiko, sangat disarankan untuk direfaktor.',
        };
    }
}
