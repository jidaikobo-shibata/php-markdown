<?php

namespace Jidaikobo\Traits;

/**
 * additional table processing features.
 *
 * This class extends the Michelf\MarkdownExtra to add support for:
 * - Row headers (indicated by a trailing colon `:` in table cells).
 * - Table captions (indicated by a leading colon `:` in rows).
 */
trait TableTrait
{
    /**
     * Finds tables, including an optional Markdown Extra attribute line.
     *
     * @param string $text The Markdown text.
     *
     * @return string The text with tables converted to protected HTML blocks.
     */
    protected function doTables($text)
    {
        $lessThanTab = $this->tab_width - 1;

        $text = preg_replace_callback('
            {
                ^[ ]{0,' . $lessThanTab . '}[|](.+) \n
                [ ]{0,' . $lessThanTab . '}[|]([ ]*[-:]+[-| :]*) \n
                (
                    (?>[ ]*[|].* \n)*
                )
                [ ]*(?:' . $this->id_class_attr_catch_re . ')[ ]* \n
                (?=\n|\Z)
            }xm', [$this, '_doTable_leadingPipe_callback'], $text);

        $text = preg_replace_callback('
            {
                ^[ ]{0,' . $lessThanTab . '}(\S.*[|].*) \n
                [ ]{0,' . $lessThanTab . '}([-:]+[ ]*[|][-| :]*) \n
                (
                    (?>.*[|].* \n)*
                )
                [ ]*(?:' . $this->id_class_attr_catch_re . ')[ ]* \n
                (?=\n|\Z)
            }xm', [$this, '_doTable_callback'], $text);

        return parent::doTables($text);
    }

    /**
     * Removes leading pipes and forwards optional table attributes.
     *
     * @param array $matches The regex matches for the table syntax.
     *
     * @return string The processed table HTML.
     */
    // The method name is defined by the parent parser's callback API.
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps,PSR2.Methods.MethodDeclaration.Underscore
    protected function _doTable_leadingPipe_callback($matches)
    {
        $content = preg_replace('/^ *[|]/m', '', $matches[3]);

        return $this->_doTable_callback([
            $matches[0],
            $matches[1],
            $matches[2],
            $content,
            $matches[4] ?? null,
        ]);
    }

    /**
     * Processes Markdown tables with custom features like row headers and captions.
     *
     * @param array $matches The regex matches for the table syntax.
     *
     * @return string The processed table HTML, hashed for further Markdown processing.
     */
    // The method name is defined by the parent parser's callback API.
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps,PSR2.Methods.MethodDeclaration.Underscore
    protected function _doTable_callback($matches)
    {
        $head       = $matches[1];
        $underline  = $matches[2];
        $content    = $matches[3];
        $id_class   = $matches[4] ?? null;
        $attr       = [];

        // Remove any trailing pipes for each line.
        $head       = preg_replace('/[|] *$/m', '', $head);
        $underline  = preg_replace('/[|] *$/m', '', $underline);
        $content    = preg_replace('/[|] *$/m', '', $content);

        // Reading alignment from header underline.
        $separators = preg_split('/ *[|] */', $underline);
        foreach ($separators as $n => $s) {
            if (preg_match('/^ *-+: *$/', $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr('right');
            } elseif (preg_match('/^ *:-+: *$/', $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr('center');
            } elseif (preg_match('/^ *:-+ *$/', $s)) {
                $attr[$n] = $this->_doTable_makeAlignAttr('left');
            } else {
                $attr[$n] = '';
            }
        }

        // Parsing span elements to ignore pipes inside.
        $head       = $this->parseSpan($head);
        $headers    = preg_split('/ *[|] */', $head);
        $col_count  = count($headers);
        $attr       = array_pad($attr, $col_count, '');

        // Write column headers.
        $table_attr_str = $this->doExtraAttributes('table', $id_class, null, []);
        $text = "<table$table_attr_str>\n";
        $text .= "<thead>\n";
        $text .= "<tr>\n";
        foreach ($headers as $n => $header) {
            if (substr(trim($header), -1) === ':') {
                $text .= "  <th$attr[$n] scope=\"row\">" . $this->runSpanGamut(trim(rtrim($header, ':'))) . "</th>\n";
            } else {
                $text .= "  <th$attr[$n] scope=\"col\">" . $this->runSpanGamut(trim($header)) . "</th>\n";
            }
        }
        $text .= "</tr>\n";
        $text .= "</thead>\n";

        // Split content by row.
        $rows = explode("\n", trim($content, "\n"));

        $text .= "<tbody>\n";
        $caption = '';
        foreach ($rows as $row_index => $row) {
            // Parsing span elements to ignore pipes inside.
            $row = $this->parseSpan($row);

            if (strpos(trim($row), ':') === 0) {
                $caption = "<caption>" . $this->runSpanGamut(trim(ltrim($row, ':'))) . "</caption>";
                continue;
            }

            // Check if the first cell is marked as a row header.
            $row_cells = preg_split('/ *[|] */', $row, $col_count);
            $row_cells = array_pad($row_cells, $col_count, '');

            $text .= "<tr>\n";
            foreach ($row_cells as $n => $cell) {
                if (substr(trim($cell), -1) === ':') {
                    $text .= "  <th$attr[$n] scope=\"row\">" . $this->runSpanGamut(trim(rtrim($cell, ':'))) . "</th>\n";
                } else {
                    $text .= "  <td$attr[$n]>" . $this->runSpanGamut(trim($cell)) . "</td>\n";
                }
            }
            $text .= "</tr>\n";
        }
        $text .= "</tbody>\n";
        $text .= "</table>";

        if (!empty($caption)) {
            $text = preg_replace(
                '/\A(<table\b[^>]*>)/',
                '$1' . "\n" . $caption,
                $text,
                1
            );
        }

        return $this->hashBlock($text) . "\n";
    }
}
