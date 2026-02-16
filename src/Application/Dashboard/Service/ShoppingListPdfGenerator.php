<?php

declare(strict_types=1);

namespace Application\Dashboard\Service;

use TCPDF;

final class ShoppingListPdfGenerator
{
    public function generate(array $products): string
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        $pdf->SetCreator('MMM - Mieux Manger en Marne');
        $pdf->SetAuthor('MMM');
        $pdf->SetTitle('Ma Liste de Course');
        
        // Supprimer header/footer par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Marges
        $pdf->SetMargins(15, 15, 15);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Logo et titre
        $pdf->SetFont('helvetica', 'B', 24);
        $pdf->SetTextColor(34, 139, 34); // Vert
        $pdf->Cell(0, 15, 'Ma Liste de Course', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Générée le ' . date('d/m/Y à H:i'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Table des produits
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(34, 139, 34);
        $pdf->SetTextColor(255, 255, 255);
        
        // En-têtes
        $pdf->Cell(70, 8, 'Produit', 1, 0, 'L', true);
        $pdf->Cell(50, 8, 'Marque', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Nutriscore', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Quantité', 1, 1, 'C', true);
        
        // Produits
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        $fill = false;
        foreach ($products as $product) {
            $pdf->SetFillColor(240, 240, 240);
            
            $pdf->Cell(70, 7, $this->truncate($product['name'], 30), 1, 0, 'L', $fill);
            $pdf->Cell(50, 7, $this->truncate($product['brands'], 20), 1, 0, 'L', $fill);
            
            // Nutriscore coloré
            $nutriscore = $product['nutriscore'] ?? 'N/A';
            $this->setNutriscoreColor($pdf, $nutriscore);
            $pdf->Cell(25, 7, $nutriscore, 1, 0, 'C', true);
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(35, 7, $this->truncate($product['quantity'] ?? '-', 15), 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        // Footer
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Total : ' . count($products) . ' produits', 0, 1, 'R');
        $pdf->Cell(0, 5, 'Données fournies par Open Food Facts', 0, 1, 'R');
        
        return $pdf->Output('liste-de-course.pdf', 'S');
    }
    
    private function setNutriscoreColor(TCPDF $pdf, string $grade): void
    {
        $colors = [
            'A' => [3, 129, 65],      // Vert foncé
            'B' => [133, 187, 47],    // Vert clair
            'C' => [254, 203, 2],     // Jaune
            'D' => [238, 129, 0],     // Orange
            'E' => [230, 62, 17],     // Rouge
        ];
        
        $color = $colors[$grade] ?? [200, 200, 200];
        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->SetTextColor(255, 255, 255);
    }
    
    private function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length - 3) . '...';
    }
}