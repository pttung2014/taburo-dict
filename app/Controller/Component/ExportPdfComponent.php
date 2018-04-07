<?php
/**
 * PDF出力で共通に使用するコンポーネント
 * @author murayama
 */
class ExportPdfComponent extends Component {
    public $components = array('Mpdf');
    public function initialize(Controller $controller) {
        $this->Controller = $controller;
    }

    /**
     * PDF書出初期設定
     * @return FPDI |
     */
    function initPDF() {
        App::import('Vendor', 'tcpdf/tcpdf');
        App::import('Vendor', 'tcpdf/fpdi');

        $pdf = new FPDI();

        $pdf->setFontSubsetting(true); // フォントの必要な文字だけ埋め込みをする

        //余白設定
        $pdf->SetMargins(0, 0, 0);

        //ヘッダー・フッター出力無効化
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        //フォント設定
        $font = new TCPDF_FONTS();
        $ipam = $font->addTTFfont(APP. 'Vendor'. DS. 'tcpdf'. DS. 'fonts'. DS. 'ipam.ttf');
        $pdf->SetFont($ipam, null, 7, true);

        //色指定
        $pdf->SetTextColor(60, 60, 60);

        return array($pdf, $ipam);
    }

    /**
     * テンプレート設定
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param string $template_name
     * @return OBJ $template
     */
    function setTemplateAddPage(&$pdf, $font, $template_name, $page_count = 1) {
        //ページ追加
        $pdf->AddPage();

        //テンプレート読込
        $pdf->setSourceFile(WWW_ROOT. 'pdf'. DS. $template_name);
        $template = $pdf->importPage($page_count);

        $pdf->useTemplate($template, null, null, null, null, true);

        //色指定
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetFont($font, null, 7, true);

        return $template;
    }

    /**
     * 価格表示
     * @param FPDI OBJ $pdf
     * @param int $height  // 表示Y座標(縦)
     * @param int $x       // 表示X座標(横)
     * @param int $price   // 価格
     * @param int || array $margin // 微調整余白
     */
    function putPricePdf(&$pdf, $height, $x, $price, $margin = 0, $showZero = true) {

        if (is_array($margin)) {
            $margin1 = (isset($margin[0])) ? $margin[0] : 0;
            $margin2 = (isset($margin[1])) ? $margin[1] : 0;
            $margin3 = (isset($margin[2])) ? $margin[2] : 0;
        } else {
            $margin1 = $margin2 = $margin3 = $margin;
        }

        if ($price == 0 && !$showZero) { return; }

        // 価格表示
        $n1 = substr($price, 0, -6); //百万
        $n2 = substr($price, -6, -3);//千
        $n3 = substr($price, -3);    //円
        $n1 = str_replace('-', '△', $n1);
        $n2 = str_replace('-', '△', $n2);
        $n3 = str_replace('-', '△', $n3);
        $pdf->SetXY($x + 0.4 + $margin1, $height);
        $pdf->MultiCell(11, 5, $n1, 0, 'R');
        $pdf->SetXY($x + 11.6 + $margin2, $height);
        $pdf->MultiCell(8, 5, $n2, 0, 'R');
        $pdf->SetXY($x + 21 + $margin3, $height);
        $pdf->MultiCell(11, 5, $n3, 0, 'L');
    }

    /**
     * 横幅指定で文字列丸め込み
     * @param string $str  丸め込みしたい文字列
     * @param int $width   １行の横幅
     * @param int $line    行数
     * @return string      丸め込み後1行分の横幅ごとに改行挿入された文字列
     */
    function roundLineStrByWidth($str, $width, $line=2) {

        // 丸め込み
        $str = (mb_strwidth($str, 'utf-8') <= $width * $line) ? $str : mb_strimwidth($str, 0, ($width * $line - 3), '', 'utf-8').'...';

        // １行の横幅で分割(改行挿入)
        $splitStr = array();
        for ($i=0; $i < $line; $i++) {
            if ($width < mb_strwidth($str, 'utf-8')) {
                $splitStr[$i] = mb_strimwidth($str, 0, $width, '', 'utf-8');
                $str = mb_substr($str, mb_strlen($splitStr[$i], 'utf-8'), null, 'utf-8');
            } else {
                $splitStr[$i] = $str;
                break;
            }
        }
        return implode("\n", $splitStr);
    }

    /**
     * 横幅指定で文字列丸め込み
     * @param string $str  丸め込みしたい文字列
     * @param int $width   １行の横幅
     * @param int $line    行数
     * @return string      丸め込み後1行分の横幅ごとに改行挿入された文字列
     */
    function roundLineStrByWidthNot($str, $width, $line=2) {

        // 丸め込み
      //  $str = (mb_strwidth($str, 'utf-8') <= $width * $line) ? $str : mb_strimwidth($str, 0, ($width * $line - 3), '', 'utf-8').'...';

        // １行の横幅で分割(改行挿入)
        $splitStr = array();
        for ($i=0; $i < $line; $i++) {
            if ($width < mb_strwidth($str, 'utf-8')) {
                $splitStr[$i] = mb_strimwidth($str, 0, $width, '', 'utf-8');
                $str = mb_substr($str, mb_strlen($splitStr[$i], 'utf-8'), null, 'utf-8');
            } else {
                $splitStr[$i] = $str;
                break;
            }
        }
        return implode("\n", $splitStr);
    }


    /**
     * 西暦を平成に変換した日付を出力
     * @param FPDI OBJ $pdf
     * @param int $height  // 表示Y座標(縦)
     * @param int $x       // 表示X座標(横)
     * @param int $date    // 西暦の日付
     * @param int || array $margin // 微調整余白
     * @param bool $put_day // 日付の日を出力するか true:出力する false:出力しない
     */
    function putHeiseiDate(&$pdf, $height, $x, $date, $margin = 0, $put_day = true) {

        if (is_array($margin)) {
            $margin1 = (isset($margin[0])) ? $margin[0] : 0;
            $margin2 = (isset($margin[1])) ? $margin[1] : 0;
            $margin3 = (isset($margin[2])) ? $margin[2] : 0;
        } else {
            $margin1 = $margin2 = $margin3 = $margin;
        }

        $heiseiDate = $this->convertHeiseiDate($date);

        $pdf->SetXY($x + $margin1, $height);
        $pdf->MultiCell(8, 5, $heiseiDate['year'], 0, 'C');
        $pdf->SetXY($x + 8 + $margin2, $height);
        $pdf->MultiCell(8, 5, $heiseiDate['month'], 0, 'C');
        if ($put_day) {
            $pdf->SetXY($x + 16 + $margin3, $height);
            $pdf->MultiCell(8, 5, $heiseiDate['day'], 0, 'C');
        }
    }

    /**
     * 西暦日付を平成日付へ変換
     * @param string $date  // 2000-10-01  2000/10/01
     * @return array array('year'=>'12', 'month'=>'10', 'day'=>'1')
     */
    function convertHeiseiDate($date) {

        $year = $month = $day = "";
        if ($date) {
            $timestamp = strtotime($date);
            $year = date('Y', $timestamp) - 1988;
            $month = date('n', $timestamp);
            $day = date('j', $timestamp);
        }

        return compact('year', 'month', 'day');
    }

  /**
   * 一括出力PDF
   * @param  array  $targets all_flg:全て出力,その他:個別選択
   * @return FPDI $pdf fpdiオブジェクト
   */
    function selectedExportPdf($targets = array()) {

        list($pdf, $font) = $this->initPDF();

        foreach ($targets as $target => $export_flg) {
            if ($target != "all" && $export_flg) {
                $method = "export_{$target}";
                if (method_exists($this, $method)) {
                    $pdf = $this->$method($pdf, $font);
                }
            }

        }

        return $pdf;

    }

    /**
    * 届出一括出力PDF
    * @param  array  $targets all_flg:全て出力,その他:個別選択
    * @return FPDI $pdf fpdiオブジェクト
    */
    function selectedExportPdfForNotificationEstablishment($targets = array()) {

      list($pdf, $font) = $this->initPDF();

      foreach ($targets as $target => $export_flg) {
        if ($target != "all" && $export_flg) {
          switch($target) {
            case 'shareholders':
              $pdf = $this->export_shareholders_list($pdf, $font);
              break;
            case 'establishment_notifications':
              $pdf = $this->export_establishment_notification($pdf, $font);
              break;
            case 'blue_colors':
              $pdf = $this->blue_color($pdf, $font);
              break;
            case 'salary_notifications':
              $pdf = $this->export_salary_notification($pdf, $font);
              break;
            case 'salary_payment_deadlines':
              $pdf = $this->export_salary_payment_deadlines($pdf, $font);
              break;
            case 'establishment_balance_sheets':
              $pdf = $this->export_establishment_balance_sheet($pdf, $font);
              break;
          }
        }
      }

      return $pdf;
    }

    /**
     * 価格表示
     *
     * @param object $pdf
     * @param int $item
     * @param int $x
     * @param int $height
     */
    function _putNumberTableItem(&$pdf, $item, $x, $y, $margin = 0, $round = false, $group = true)
    {
        if (!empty($item) || $item == 0) {
        $x += 17.9;
        if (!empty($margin)) {
            $margin1 = $margin[0];
            $margin2 = $margin[1];
            //Margin of △
            $margin3 = isset($margin[2]) ? $margin[2] : null;
        } else {
            $margin1 = 3.3;
            $margin2 = 2.2;
            $margin3 = null;
        }
        $length = mb_strlen($item);
        $check = !empty($round) ? round($item/$round) : true;
        if (!empty($check)) {
            if (!empty($round)) {
                $item   = (($item/$round) != 0) ? (int)($item/$round) : 1;
                $length = mb_strlen($item);
            }
            for($num = 0; $num < $length; $num ++) {
                $numStart = ($num == 0) ? null : - $num;
                $numEnd   = ($num == ($length - 1)) ? 0 : $numStart - 1;
                $m        = !empty($numStart) ? substr($item, $numEnd, $numStart) : substr($item, $numEnd);
                //Set margin of △
                if ($num == ($length -1)) {
                    $m = ($m == '-') ? '△' : $m;
                    if (!empty($margin3)) {
                        $margin1 = ($m == '△') ? $margin3 : $margin1;
                        $margin2 = ($m == '△') ? $margin3 : $margin2;
                    } else {
                        $margin1 = ($m == '△') ? 2.0 : $margin1;
                        $margin2 = ($m == '△') ? 1.4 : $margin2;
                    }
                }

                //Set x y
                if (!empty($group) && $round == 100) {
                    if ($num == 0) {
                        $x = $x - $margin2 + 0.6;
                    } else {
                        $x = (($num % 3 == 1)) ? $x - $margin1 : $x - $margin2;
                    }
                } else {
                    $x = (($num % 3 == 0) && $num != 0) ? $x - $margin1 : $x - $margin2;
                }
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(20, 5, $m, 0, 'R');
            }
        }
        }
    }

    /**
     * 価格表示(0の場合表示しない)
     *
     * @param object $pdf
     * @param int $item
     * @param int $x
     * @param int $height
     */
    function _putNumberTableItemNon0(&$pdf, $item, $x, $y, $margin = 0, $round = false, $group = true)
    {
        if (!empty($item) || $item == 0) {
        $x += 17.9;
        if (!empty($margin)) {
            $margin1 = $margin[0];
            $margin2 = $margin[1];
            //Margin of △
            $margin3 = isset($margin[2]) ? $margin[2] : null;
        } else {
            $margin1 = 3.3;
            $margin2 = 2.2;
            $margin3 = null;
        }
        $length = mb_strlen($item);
        $check = !empty($round) ? round($item/$round) : true;
        if (!empty($check)) {
            if (!empty($round)) {
                $item   = (($item/$round) != 0) ? (int)($item/$round) : 1;
                $length = mb_strlen($item);
            }
            for($num = 0; $num < $length; $num ++) {
                $numStart = ($num == 0) ? null : - $num;
                $numEnd   = ($num == ($length - 1)) ? 0 : $numStart - 1;
                $m        = !empty($numStart) ? substr($item, $numEnd, $numStart) : substr($item, $numEnd);
                //Set margin of △
                if ($num == ($length -1)) {
                    $m = ($m == '-') ? '△' : $m;
                    if (!empty($margin3)) {
                        $margin1 = ($m == '△') ? $margin3 : $margin1;
                        $margin2 = ($m == '△') ? $margin3 : $margin2;
                    } else {
                        $margin1 = ($m == '△') ? 2.0 : $margin1;
                        $margin2 = ($m == '△') ? 1.4 : $margin2;
                    }
                }

                //Set x y
                if (!empty($group) && $round == 100) {
                    if ($num == 0) {
                        $x = $x - $margin2 + 0.6;
                    } else {
                        $x = (($num % 3 == 1)) ? $x - $margin1 : $x - $margin2;
                    }
                } else {
                    $x = (($num % 3 == 0) && $num != 0) ? $x - $margin1 : $x - $margin2;
                }
                $pdf->SetXY($x, $y);
                if($item == 0){
                  $m = NULL;
                }
                $pdf->MultiCell(20, 5, $m, 0, 'R');
            }
        }
        }
    }


    /**
     * 同族会社の判定明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules2s($pdf, $font) {

        //事業年度で様式選択
        $Shareholder = ClassRegistry::init('Shareholder');
        $term_info = $Shareholder->getCurrentTerm();
        $target_day = '2016/01/01';
        $target_day29 = '2017/04/01';
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $template = $this->setTemplateAddPage($pdf, $font, 'schedules2_e290401.pdf');
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schdule02.pdf');
        } else {
          $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules2.pdf');
        }

        $shareholders = $Shareholder->findForPDF();

        $pdf->SetFont($font, null, 9, true); //フォントサイズ変更

        //色指定
        $pdf->SetTextColor(20, 20, 20);

        // 事業年度又は連結事業年度
        $pdf->SetXY(120, 13);
        $pdf->MultiCell(8, 5, $shareholders['term_start'][0], 0, 'C');
        $pdf->SetXY(129, 13);
        $pdf->MultiCell(8, 5, $shareholders['term_start'][1], 0, 'C');
        $pdf->SetXY(138, 13);
        $pdf->MultiCell(8, 5, $shareholders['term_start'][2], 0, 'C');
        $pdf->SetXY(120, 18.5);
        $pdf->MultiCell(8, 5, $shareholders['term_end'][0], 0, 'C');
        $pdf->SetXY(129, 18.5);
        $pdf->MultiCell(8, 5, $shareholders['term_end'][1], 0, 'C');
        $pdf->SetXY(138, 18.5);
        $pdf->MultiCell(8, 5, $shareholders['term_end'][2], 0, 'C');

        //法人名
        $pdf->SetXY(160, 12);
        $pdf->MultiCell(35, 11, $shareholders['name'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        //$pdf->MultiCell(35, 11, $shareholders['name'].$shareholders['name'].$shareholders['name'].$shareholders['name'].$shareholders['name'], 1, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        //期末現在の発行済株式の総数又は出資の総額
        if ($shareholders[1][0] != 0) {
            $pdf->SetFont($font, null, 9, true);
            $pdf->SetXY(86, 24.5);
            $pdf->MultiCell(25, 5, number_format($shareholders[1][0]), 0, 'R');
        }
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(83, 29.5);
        $pdf->MultiCell(28, 5, number_format($shareholders[1][1]), 0, 'R');

        //(19)と(21)の上位３順位の株式数又は出資の金額
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(83, 40);
        $pdf->MultiCell(28, 5, number_format($shareholders[2]), 0, 'R');

        //株式数等による判定
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(83, 49.5);
        $pdf->MultiCell(28, 5, $shareholders[3], 0, 'R');

        if($shareholders[7] != 0){
          //期末現在の社員の総数
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(83, 90);
          $pdf->MultiCell(28, 5, number_format($shareholders[7]), 0, 'R');
        }

        //社員の３人以下及びこれらの同族関係者の合計人数のうち最も多い数
        if($shareholders[8] != 0){
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(83, 99.5);
          $pdf->MultiCell(28, 5, number_format($shareholders[8]), 0, 'R');
        }

        //社員の数による判定
        if($shareholders[9] != 0){
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(83, 110);
          $pdf->MultiCell(28, 5, $shareholders[9], 0, 'R');
        }

        //同族会社の判定割合
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(83, 119.5);
        $pdf->MultiCell(28, 5, $shareholders[10], 0, 'R');

        //判定結果
        $pdf->SetFont($font, null, 8, true);
        if ($shareholders[10] > 50) {
            // 同族会社
            $pdf->SetXY(169, 115.2);
            $pdf->MultiCell(28, 3, '', 1);
        } else {
            // 非同族会社
            $pdf->SetXY(169, 120.2);
            $pdf->MultiCell(28, 3, '', 1);
        }

        // 明細行
        $line = 0;
        $pdf->setFillColor(255);
        foreach ($shareholders['detail'] as $key => $group) {
            foreach ($group['datas'] as $g_key => $shareholder) {
                if ($line >= 13) {
                    //ページ追加
                    $pdf->AddPage();
                    $pdf->useTemplate($template, null, null, null, null, true);
                    $line = 0;

                    $pdf->SetFont($font, null, 9, true); //フォントサイズ変更

                    // 事業年度又は連結事業年度
                    $pdf->SetXY(120, 13);
                    $pdf->MultiCell(8, 5, $shareholders['term_start'][0], 0, 'C');
                    $pdf->SetXY(129, 13);
                    $pdf->MultiCell(8, 5, $shareholders['term_start'][1], 0, 'C');
                    $pdf->SetXY(138, 13);
                    $pdf->MultiCell(8, 5, $shareholders['term_start'][2], 0, 'C');
                    $pdf->SetXY(120, 18.5);
                    $pdf->MultiCell(8, 5, $shareholders['term_end'][0], 0, 'C');
                    $pdf->SetXY(129, 18.5);
                    $pdf->MultiCell(8, 5, $shareholders['term_end'][1], 0, 'C');
                    $pdf->SetXY(138, 18.5);
                    $pdf->MultiCell(8, 5, $shareholders['term_end'][2], 0, 'C');

                    //法人名
                    $pdf->SetXY(160, 12);
                    $pdf->MultiCell(35, 11, $shareholders['name'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
                }
                if ($key >= 3 and $group['vote_num'] >= 3) {
                    continue;
                }

                $pdf->SetFont($font, null, 9, true);

                // 株式数式
                $pdf->SetXY(26.5, 156.5 + 9 * $line);
                if ($key + 1 <= 3) {
                    $pdf->MultiCell(5, 3, $key + 1, 0, 'C');
                }


                // 議決権数
                $pdf->SetXY(33.5, 156.5 + 9 * $line);
                if ($group['vote_num'] + 1 <= 3) {
                    $pdf->MultiCell(5, 3, $group['vote_num'] + 1, 0, 'C');
                }


                // 住所
                $pdf->SetFont($font, null, 8, true);
                $pdf->SetXY(39, 154 + 9 * $line);
                $pdf->MultiCell(36, 5, $shareholder['address'], 0, 'L', 0, 1, '', '', true, 0, false, true, 8.5, 'M', true);

                // 氏名
                $pdf->SetFont($font, null, 8, true);
                $pdf->SetXY(75, 154 + 9 * $line);
                $pdf->MultiCell(36, 5, $shareholder['name'], 0, 'C', 0, 1, '', '', true, 0, false, true, 8.5, 'M', true);

                // 続柄
                $pdf->SetFont($font, null, 10, true);
                $pdf->Rect(111.5, 154.2 + 9 * $line, 16, 8, 'F'); //本人削除
                $pdf->SetXY(111, 154 + 9 * $line);
                $pdf->MultiCell(17, 5, $shareholder['relationship'], 0, 'C', 0, 1, '', '', true, 0, false, true, 8.5, 'M', true);

                $pdf->SetFont($font, null, 8, true);
                // 法人
                if (!empty($shareholder['19'])) {
                    $pdf->SetXY(127.9, 158.5 + 9 * $line);
                    $pdf->MultiCell(18.5, 4, number_format($shareholder['19']), 0, 'R');
                }
                if (!empty($shareholder['20'])) {
                    $pdf->SetXY(145.5, 158.5 + 9 * $line);
                    $pdf->MultiCell(18, 4, number_format($shareholder['20']), 0, 'R');
                }

                // その他
                if (!empty($shareholder['21'])) {
                    $pdf->SetXY(163, 158.5 + 9 * $line);
                    $pdf->MultiCell(18.5, 4, number_format($shareholder['21']), 0, 'R');
                }
                if ($group['flg'][3]) {
                  if (!empty($shareholder['22'])) {
                      $pdf->SetXY(180, 158.5 + 9 * $line);
                      $pdf->MultiCell(18.5, 4, number_format($shareholder['22']), 0, 'R');
                  }
                }

                $line += 1;
            }
        }
        if ($group['flg'][3]) {
          //期末現在の議決権の総数
          if ($shareholders[4][0] != 0) {
              $pdf->SetFont($font, null, 9, true);
              $pdf->SetXY(86, 55);
              $pdf->MultiCell(25, 5, number_format($shareholders[4][0]), 0, 'R');
          }
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(83, 59);
          $pdf->MultiCell(28, 5, number_format($shareholders[4][1]), 0, 'R');


          //(20)と(22)の上位３順位の議決権の数
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(83, 68.5);
          $pdf->MultiCell(28, 5, number_format($shareholders[5]), 0, 'R');


          //議決権の数による判定
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(83, 79);
          $pdf->MultiCell(28, 5, $shareholders[6], 0, 'R');


        }

        return $pdf;
    }

    function export_schedules14sub($pdf, $font) {
      // データ取得
      $ConsideredDonation = ClassRegistry::init('ConsideredDonation');
      $considereds = $ConsideredDonation->findForIndex();

      //事業年度で様式選択
      $Schedules14 = ClassRegistry::init('Schedules14');
      $term_info = $Schedules14->getCurrentTerm();
      $target_day = '2016/04/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules14_sub_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14_sub.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 'e280401schedules14_sub.pdf');
      }

      $pdf->SetAutoPageBreak(FALSE);

      $Term = ClassRegistry::init('Term');
      $user = CakeSession::read('Auth.User');
      $term_id = $user['term_id'];
      $term = $Term->find('first',array('conditions'=>array('Term.id'=>$term_id,)));

      $y = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
      $m = date('n',strtotime($term['Term']['account_beggining'])) ;
      $d = date('j',strtotime($term['Term']['account_beggining'])) ;

      $pdf->SetFont($font, null, 11, true);
      $name = $y;
      $pdf->SetXY(89, 15.5);
      $pdf->MultiCell(38, 5, $name, 0, 'R');
      $name = $m;
      $pdf->SetXY(98, 15.5);
      $pdf->MultiCell(38, 5, $name, 0, 'R');
      $name = $d;
      $pdf->SetXY(106, 15.5);
      $pdf->MultiCell(38, 5, $name, 0, 'R');

      $y = date('Y',strtotime($term['Term']['account_end'])) -1988;
      $m = date('n',strtotime($term['Term']['account_end'])) ;
      $d = date('j',strtotime($term['Term']['account_end'])) ;

      $pdf->SetFont($font, null, 11, true);
      $name = $y;
      $pdf->SetXY(89, 20);
      $pdf->MultiCell(38, 5, $name, 0, 'R');
      $name = $m;
      $pdf->SetXY(98, 20);
      $pdf->MultiCell(38, 5, $name, 0, 'R');
      $name = $d;
      $pdf->SetXY(106, 20);
      $pdf->MultiCell(38, 5, $name, 0, 'R');

      // 名称
      $pdf->SetFont($font, null, 9, true);
      $user_name = substr($user['name'],0,84);
      $height = (mb_strwidth($user_name, 'utf8') <= 23) ? 17.5 : 14.8;
      $pdf->SetXY(158.2, $height);
      $pdf->MultiCell(37, 5, $user_name, 0, 'L');

      // みなし金額
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 28);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['considered_sum']), 0, 'R');

      // 2
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 34.5);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['data2']), 0, 'R');

      // 3
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(174, 31.5);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['data3']), 0, 'R');

      // 経常費用
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 45);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['ordinary_expenses']), 0, 'R');

      // 償却費
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 50.5);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['depreciations']), 0, 'R');

      // 30の計
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 58);
      $pdf->MultiCell(18, 5, number_format($considereds['sub1']['data30']), 0, 'R');

      // 42の計
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 67);
      $pdf->MultiCell(18, 5, number_format($considereds['sub2']['data42']), 0, 'R');

      // 保有財産取得支出額
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 75);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['public_get_sum']), 0, 'R');

      // 保有財産取得以外支出額
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 82);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['nopublic_get_sum']), 0, 'R');

      // 差し引き計
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(86, 93);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['data10']), 0, 'R');

      // 経常利益
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 47);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['ordinary_income']), 0, 'R');

      // 22の計
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 57);
      $pdf->MultiCell(18, 5, number_format($considereds['sub1']['data22']), 0, 'R');

      // 35の計
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 67);
      $pdf->MultiCell(18, 5, number_format($considereds['sub2']['data35']), 0, 'R');

      // 保有財産処分支出額
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 75);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['sale_stock']), 0, 'R');

      // 15
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 82);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['public_stock_sum']), 0, 'R');

      // 16
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 89.5);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['ConsideredDonation']['transfer_sum']), 0, 'R');

      // 17
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(175, 97);
      $pdf->MultiCell(18, 5, number_format($considereds['main']['data17']), 0, 'R');

      //sub1
      for($i=0;$i<3;$i++) {
        if (empty($considereds['sub1'][$i]) or empty($considereds['sub1'][$i]['ReserveFund']['name'])) continue;
        //18
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 108);
        $pdf->MultiCell(27, 5, $considereds['sub1'][$i]['ReserveFund']['name'], 0, 'C');

        //19
        list($Y, $m, $d) = explode('-',$considereds['sub1'][$i]['ReserveFund']['last_day']);
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(88 + 27.1 * $i, 113);
        $pdf->MultiCell(7, 5, $Y - 1988, 0, 'L');
        $pdf->SetXY(98 + 27.5 * $i, 113);
        $pdf->MultiCell(7, 5, $m, 0, 'L');
        $pdf->SetXY(105 + 27.5 * $i, 113);
        $pdf->MultiCell(7, 5, $d, 0, 'L');

        //20
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 118);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['fund']), 0, 'R');

        //21
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 123.5);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['reversal']), 0, 'R');

        //22
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 131);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['decrease']), 0, 'R');

        //23
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 141);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['increase']), 0, 'R');

        //24
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 148.2);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['limit_sum']), 0, 'R');

        //25
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 153.5);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['past_fund']), 0, 'R');

        //26
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 158.5);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['past_reversal']), 0, 'R');

        //27
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 166.4);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['cal_limit']), 0, 'R');

        //28
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 173);
        $pdf->MultiCell(27, 5, $considereds['sub1'][$i]['ReserveFund']['this_months'], 0, 'C');
        $pdf->SetXY(86 + 27.1 * $i, 176.5);
        $pdf->MultiCell(27, 5, $considereds['sub1'][$i]['ReserveFund']['total_months'], 0, 'C');

        //29
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 181.5);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['base_sum']), 0, 'R');

        //30
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 189);
        $pdf->MultiCell(24, 5, number_format($considereds['sub1'][$i]['ReserveFund']['cal_res']), 0, 'R');
      }

      //22
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(167, 131);
      $pdf->MultiCell(24, 5, number_format($considereds['sub1']['data22']), 0, 'R');

      //30
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(167, 189);
      $pdf->MultiCell(24, 5, number_format($considereds['sub1']['data30']), 0, 'R');

      //sub2
      for($i=0;$i<3;$i++) {
        if (empty($considereds['sub2'][$i]) or empty($considereds['sub2'][$i]['PublicStockFund']['name'])) continue;
        //31
        $pdf->SetFont($font, null, 6.8, true);
        //$pdf->SetXY(86 + 27.1 * $i, 108);
        $pdf->SetXY(86 + 27.1 * $i, 201);
        $pdf->MultiCell(27, 5, $considereds['sub2'][$i]['PublicStockFund']['name'], 0, 'C');

        //32
        list($Y, $m, $d) = explode('-',$considereds['sub2'][$i]['PublicStockFund']['last_day']);
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(88 + 27.1 * $i, 207.5);
        $pdf->MultiCell(7, 5, $Y - 1988, 0, 'L');
        $pdf->SetXY(98 + 27.5 * $i, 207.5);
        $pdf->MultiCell(7, 5, $m, 0, 'L');
        $pdf->SetXY(105 + 27.5 * $i, 207.5);
        $pdf->MultiCell(7, 5, $d, 0, 'L');

        //33
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 212.5);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['this_fund']), 0, 'R');

        //34
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 218);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['previous_fund']), 0, 'R');

        //35
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 225);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['decrease']), 0, 'R');

        //36
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 234);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['increase']), 0, 'R');

        //37
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 243);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['limit_sum']), 0, 'R');

        //38
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 249);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['previous_fund']), 0, 'R');

        //39
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 257);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['cal_limit']), 0, 'R');

        //40
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 263.8);
        $pdf->MultiCell(27, 5, $considereds['sub2'][$i]['PublicStockFund']['this_months'], 0, 'C');
        $pdf->SetXY(86 + 27.1 * $i, 266.8);
        $pdf->MultiCell(27, 5, $considereds['sub2'][$i]['PublicStockFund']['total_months'], 0, 'C');

        //41
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 272);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['base_sum']), 0, 'R');

        //42
        $pdf->SetFont($font, null, 6.8, true);
        $pdf->SetXY(86 + 27.1 * $i, 279.5);
        $pdf->MultiCell(24, 5, number_format($considereds['sub2'][$i]['PublicStockFund']['cal_res']), 0, 'R');
      }

      //35
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(167, 225);
      $pdf->MultiCell(24, 5, number_format($considereds['sub2']['data35']), 0, 'R');

      //42
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(167, 279.5);
      $pdf->MultiCell(24, 5, number_format($considereds['sub2']['data42']), 0, 'R');

      return $pdf;
    }

    /**
     * 別表15 支出交際費等内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules15s($pdf, $font) {

        //事業年度で様式選択
        $model = ClassRegistry::init('Schedules15');
        $term_info = $model->getCurrentTerm();
        $target_day = '2016/01/01';
        $target_day29 = '2017/04/01';
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $template = $this->setTemplateAddPage($pdf, $font, 'schedules15_e290401.pdf');
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $template = $this->setTemplateAddPage($pdf, $font, 'schedules15s.pdf');
        } else {
          $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules15.pdf');
        }

        $Term = ClassRegistry::init('Term');
        $schedules15s =  $model->findPdfExportData();

        $point_step = 15.5;   // 次の出力(差)
        $point_y = 103;       // 出力開始位置(縦)

        $record_count = 0;
        $balance_sum = 0;

        $user = CakeSession::read('Auth.User');
        $term_id = $user['term_id'];

        $pdf->SetFont($font, null, 12, true);

        $data2 = floor($schedules15s['total']['total_drinking_sum'] / 2 );

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
            )));

        $y = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
        $m = date('n',strtotime($term['Term']['account_beggining'])) ;
        $d = date('j',strtotime($term['Term']['account_beggining'])) ;

        $pdf->SetFont($font, null, 10, true);
        $name = $y;
        $pdf->SetXY(88, 16);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = $m;
        $pdf->SetXY(94, 16);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = $d;
        $pdf->SetXY(102, 16);
        $pdf->MultiCell(38, 5, $name, 0, 'R');

        $y = date('Y',strtotime($term['Term']['account_end'])) -1988;
        $m = date('n',strtotime($term['Term']['account_end'])) ;
        $d = date('j',strtotime($term['Term']['account_end'])) ;

        $pdf->SetFont($font, null, 10, true);
        $name = $y;
        $pdf->SetXY(88, 20.5);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = $m;
        $pdf->SetXY(94, 20.5);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = $d;
        $pdf->SetXY(102, 20.5);
        $pdf->MultiCell(38, 5, $name, 0, 'R');

        // 名称
        $pdf->SetFont($font, null, 9, true);
        $user_name = substr($user['name'],0,84);
        $height = (mb_strwidth($user_name, 'utf8') <= 29) ? 18.5 : 16.8;
        $pdf->SetXY(153.2, $height);
        $pdf->MultiCell(47, 5, $user_name, 0, 'L');
        $pdf->SetFont($font, null, 10, true);

        $name = number_format($schedules15s['data1']);
        $pdf->SetXY(73, 31.5);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = number_format($schedules15s['data2']);
        $pdf->SetXY(73, 47.5);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = number_format($schedules15s['data3']);
        $pdf->SetXY(73, 62.5);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = number_format($schedules15s['data4']);
        $pdf->SetXY(158, 35);
        $pdf->MultiCell(38, 5, $name, 0, 'R');
        $name = number_format($schedules15s['data5']);
        $pdf->SetXY(158, 59);
        $pdf->MultiCell(38, 5, $name, 0, 'R');

        $pdf->SetFont($font, null, 10, true);

		//各ページごとの計
		$total_page = array(0,0,0,0);

        //科目の均等割り付け対応
        //当該フォントにおける最大値（１１文字）と１文字のpx幅取得
        $total_length = $pdf->GetStringWidth("あああああああああああ");
        $char_length = $pdf->GetStringWidth("あ");

        foreach ($schedules15s as $key => $data) {

            if(is_numeric($key)){

				$record_count++;

				if ($record_count > 11) {
					// 現在のページの計
					$pdf->SetAutoPageBreak(true, 0);

					$name = number_format($total_page[0]); //支出額
					$pdf->SetXY(65, 275.2);
					$pdf->MultiCell(38, null, $name, 0, 'R');
					$name = number_format($total_page[1]); //交際費等の額から控除される費用の額
					$pdf->SetXY(96, 275.2);
					$pdf->MultiCell(38, null, $name, 0, 'R');
					$name = number_format($total_page[2]); //差引交際費等の額
					$pdf->SetXY(127, 275.2);
					$pdf->MultiCell(38, null, $name, 0, 'R');
					$name = number_format($total_page[3]); //接待飲食費の額
					$pdf->SetXY(160, 275.2);
					$pdf->MultiCell(38, null, $name, 0, 'R');

					$pdf->SetAutoPageBreak(true, 2);

					$total_page = array(0,0,0,0); //ページ合計初期化

					// 次ページ目
					$pdf->addPage();
					$pdf->useTemplate($template, null, null, null, null, true);
					$record_count = 2;
					$point_y = 103;
					//事業年度・法人名
					$y = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
					$m = date('n',strtotime($term['Term']['account_beggining'])) ;
					$d = date('j',strtotime($term['Term']['account_beggining'])) ;

					$pdf->SetFont($font, null, 10, true);
					$name = $y;
					$pdf->SetXY(88, 16);
					$pdf->MultiCell(38, 5, $name, 0, 'R');
					$name = $m;
					$pdf->SetXY(94, 16);
					$pdf->MultiCell(38, 5, $name, 0, 'R');
					$name = $d;
					$pdf->SetXY(102, 16);
					$pdf->MultiCell(38, 5, $name, 0, 'R');

					$y = date('Y',strtotime($term['Term']['account_end'])) -1988;
					$m = date('n',strtotime($term['Term']['account_end'])) ;
					$d = date('j',strtotime($term['Term']['account_end'])) ;

					$pdf->SetFont($font, null, 10, true);
					$name = $y;
					$pdf->SetXY(88, 20.5);
					$pdf->MultiCell(38, 5, $name, 0, 'R');
					$name = $m;
					$pdf->SetXY(94, 20.5);
					$pdf->MultiCell(38, 5, $name, 0, 'R');
					$name = $d;
					$pdf->SetXY(102, 20.5);
					$pdf->MultiCell(38, 5, $name, 0, 'R');

					// 名称
					$pdf->SetFont($font, null, 9, true);
					$user_name = substr($user['name'],0,84);
					$height = (mb_strwidth($user_name, 'utf8') <= 29) ? 18.5 : 16.8;
					$pdf->SetXY(153.2, $height);
					$pdf->MultiCell(47, 5, $user_name, 0, 'L');
					$pdf->SetFont($font, null, 10, true);

					//交際費０挿入
					// 支出額
					$height = $point_y + 2;
					$pdf->SetXY(65, $height);
					$pdf->MultiCell(38, 5, '0', 0, 'R');

					// 控除額
					$height = $point_y + 2;
					$pdf->SetXY(96, $height);
					$pdf->MultiCell(38, 5, '0', 0, 'R');

					// 差引額
					$height = $point_y + 2;
					$pdf->SetXY(127, $height);
					$pdf->MultiCell(40, 5, '0', 0, 'R');

					// 接待飲食費
					$height = $point_y + 2;
					$pdf->SetXY(160, $height);
					$pdf->MultiCell(38, 5, '0', 0, 'R');

					$point_y += $point_step;
				}

                // 科目
                if($key >0) {
                    $account_title = $data['AccountTitle']['account_title'];
                    $char_num   = mb_strlen($account_title,'utf-8') - 1;
                    $cell_length = ($total_length - $char_length) / $char_num;
                    $height = (mb_strwidth($account_title, 'utf8') <= 12) ? $point_y + 2 : $point_y;
                    $pdf->SetXY(29,$height );
                    // 1文字づつセルに入れていく
                    for ($i = 0; $i < $char_num; $i++) {
                        $pdf->Cell($cell_length, null, mb_substr($account_title, $i, 1,'utf-8'), 0);
                    }
                    $pdf->Cell($char_length, null, mb_substr($account_title, $char_num, 1,'utf-8'), 0);
                }

                // 支出額
                $name = h(number_format($data['Schedules15']['pay_sum']));
                $height = $point_y + 2;
                $pdf->SetXY(65, $height);
                $pdf->MultiCell(38, 5, $name, 0, 'R');
				$total_page[0] += $data['Schedules15']['pay_sum'];

                // 控除額
                $name = h(number_format($data['Schedules15']['deducted_sum']));
                $height = $point_y + 2;
                $pdf->SetXY(96, $height);
                $pdf->MultiCell(38, 5, $name, 0, 'R');
				$total_page[1] += $data['Schedules15']['deducted_sum'];

                // 差引額
                $name = h(number_format($data['Schedules15']['net_sum']));
                $height = $point_y + 2;
                $pdf->SetXY(127, $height);
                $pdf->MultiCell(40, 5, $name, 0, 'R');
				$total_page[2] += $data['Schedules15']['net_sum'];

                // 接待飲食費
                $name = h(number_format($data['Schedules15']['drinking_sum']));
                $height = $point_y + 2;
                $pdf->SetXY(160, $height);
                $pdf->MultiCell(38, 5, $name, 0, 'R');
				$total_page[3] += $data['Schedules15']['drinking_sum'];

                $point_y += $point_step;
            }
        }

        $pdf->SetAutoPageBreak(true, 0);

		$name = number_format($total_page[0]); //支出額
        $pdf->SetXY(65, 275.2);
        $pdf->MultiCell(38, null, $name, 0, 'R');
		$name = number_format($total_page[1]); //交際費等の額から控除される費用の額
        $pdf->SetXY(96, 275.2);
        $pdf->MultiCell(38, null, $name, 0, 'R');
		$name = number_format($total_page[2]); //差引交際費等の額
        $pdf->SetXY(127, 275.2);
        $pdf->MultiCell(38, null, $name, 0, 'R');
		$name = number_format($total_page[3]); //接待飲食費の額
        $pdf->SetXY(160, 275.2);
        $pdf->MultiCell(38, null, $name, 0, 'R');

        $pdf->SetAutoPageBreak(true, 2);

        return $pdf;
    }

    /**
     * 別表16⑴ 旧定額法又は定額法による減価償却資産の償却額の計算に関する明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules1601s($pdf, $font) {

      $FixedAsset = ClassRegistry::init('FixedAsset');

      //事業年度で様式選択
      $term_info = $FixedAsset->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules16_1_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules16_01.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules16_1.pdf');
      }

        $Term = ClassRegistry::init('Term');
        $datas = $FixedAsset->findTotalfor1601(null,null);

    	$point_start_y = 37;      // 出力開始位置起点(縦)
    	$point_step = 15.5;          // 次の出力
    	$point_y = $height = 22.5;  // 出力開始位置(縦)

    	$record_count = 0;
    	$balance_sum = 0;

    	//事業年度の月数を取得
    	$pdf->SetFont($font, null, 5, true);
    	$months = $FixedAsset->getCurrentYear();
    	// $height = $point_y + 2;
    	// $pdf->SetXY(66.4, 147.5);
    	// $pdf->MultiCell(38, 5, $months, 0, 'L');

    	$term_id = CakeSession::read('Auth.User.term_id');

    	$pdf->SetFont($font, null, 12, true);

    	$term = $Term->find('first',array(
			'conditions'=>array('Term.id'=>$term_id,
    	)));

    	$y1 = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
    	$m1 = date('n',strtotime($term['Term']['account_beggining'])) ;
    	$d1 = date('j',strtotime($term['Term']['account_beggining'])) ;

    	$pdf->SetFont($font, null, 10, true);
    	$pdf->SetXY(87, 12);
    	$pdf->MultiCell(38, 5, $y1, 0, 'R');
    	$pdf->SetXY(94, 12);
    	$pdf->MultiCell(38, 5, $m1, 0, 'R');
    	$pdf->SetXY(102, 12);
    	$pdf->MultiCell(38, 5, $d1, 0, 'R');

    	$y2 = date('Y',strtotime($term['Term']['account_end'])) -1988;
    	$m2 = date('n',strtotime($term['Term']['account_end'])) ;
    	$d2 = date('j',strtotime($term['Term']['account_end'])) ;

    	$pdf->SetFont($font, null, 10, true);
    	$pdf->SetXY(87, 17.5);
    	$pdf->MultiCell(38, 5, $y2, 0, 'R');
    	$pdf->SetXY(94, 17.5);
    	$pdf->MultiCell(38, 5, $m2, 0, 'R');
    	$pdf->SetXY(102, 17.5);
    	$pdf->MultiCell(38, 5, $d2, 0, 'R');

    	// 法人名
    	$pdf->SetFont($font, null, 9, true);
    	$user_name = CakeSession::read('Auth.User.name');
      $user_name = substr($user_name,0,84);
    	$height = (mb_strwidth($user_name, 'utf8') <= 29) ? 14.5 : 12.5;

    	if(mb_strwidth($user_name, 'utf8') > 56){
		}

		$pdf->SetXY(152.2, $height);
		$pdf->MultiCell(47, 5, $user_name, 0, 'L');
		$pdf->SetFont($font, null, 6.8, true);

		$x = 71;

		foreach ($datas as $key => $data) {
			$record_count++;

			if($record_count >  5) {
				$pdf->AddPage();
				$pdf->useTemplate($template, null, null, null, null, true);
				$record_count=0;
				$x = 71;

				$pdf->SetFont($font, null, 10, true);
				$pdf->SetXY(87, 12);
				$pdf->MultiCell(38, 5, $y1, 0, 'R');
				$pdf->SetXY(94, 12);
				$pdf->MultiCell(38, 5, $m1, 0, 'R');
				$pdf->SetXY(102, 12);
				$pdf->MultiCell(38, 5, $d1, 0, 'R');
				$pdf->SetXY(87, 17.5);
				$pdf->MultiCell(38, 5, $y2, 0, 'R');
				$pdf->SetXY(94, 17.5);
				$pdf->MultiCell(38, 5, $m2, 0, 'R');
				$pdf->SetXY(102, 17.5);
				$pdf->MultiCell(38, 5, $d2, 0, 'R');
				$pdf->SetFont($font, null, 9, true);
				$user_name = CakeSession::read('Auth.User.name');;

				$height = (mb_strwidth($user_name, 'utf8') <= 29) ? 14.5 : 12.5;

				$pdf->SetXY(152.2, $height);
				$pdf->MultiCell(47, 5, $user_name, 0, 'L');
				//事業年度の月数を取得
				$pdf->SetFont($font, null, 5, true);
				$months = $FixedAsset->getCurrentYear();
				$height = $point_y + 2;
				$pdf->SetXY(66.4, 147.5);
				$pdf->MultiCell(38, 5, $months, 0, 'L');

				$pdf->SetFont($font, null, 6.8, true);

			}

			// 1種類
			$name = "";
			$name = h($data['account_title']);
			$height = $point_y + 2;
			$pdf->SetXY($x, $height);
			$pdf->MultiCell(38, 5, $name, 0, 'C');

			// 7取得価額又は製作価額
			$name = "";
			$name = h(number_format($data['cost']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 8圧縮記帳による積立金計上額
			$name = "";
			$name = h(number_format($data['compression_sum']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 6 );
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 9差引取得価額 (7)-(8)
			$name = "";
			$name = h(number_format($data['data9']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 11.4 );
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 13差引帳簿記載金額
			$name = "";
			$name = h(number_format($data['data13']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 33.2);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 14損金に計上した当期償却額
			$name = "";
			$name = h(number_format($data['depreciation_sum']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 38.8);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 15前期から繰り越した償却超過額
			$name = "";
			$name = h(number_format($data['previous_excess_sum']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 44.3);
			//$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 16合計　(13)+(14)+(15)
			$name = "";
			$name = h(number_format($data['data16']));
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 49.5);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 17残存価額
			$name = "";
			if(isset($data['method'])){
				if($data['method'] == '旧定額法'){
					if(isset($data['zanzon_price'])){
						$name = number_format(h($data['zanzon_price']));
						if($name == 0) $name = '';
					}
				}
				$height = $point_y + 2;
				$pdf->SetXY($x, $height + 31 + 54.7);
				$pdf->MultiCell(28, 5, $name, 0, 'R');
			}

			// 18差引取得価額(9)×5%
			$name = "";
			if(isset($data['method'])){
				if($data['method'] == '旧定額法'){
					if(isset($data['zanzon_price'])){
						$name = number_format(h($data['data18']));
						if($name == 0) $name = '';
					}
				}
				$height = $point_y + 2;
				$pdf->SetXY($x, $height + 31 + 59.9);
				$pdf->MultiCell(28, 5, $name, 0, 'R');
			}

			// 19旧定額法の償却額計
			$name = "";
			if(isset($data['data19'])){
				$name = number_format(h($data['data19']));
				if($name == 0) $name = '';
				$height = $point_y + 2;
				$pdf->SetXY($x, $height + 31 + 65.3);
				$pdf->MultiCell(28, 5, $name, 0, 'R');
			}

			// 20旧定額法の償却率
			$name = "";
			if(isset($data['method'])){
				if($data['method'] == '旧定額法'){
					if($data['data16'] > $data['data18']){
						$name = h($data['rate']);
						if($name == 0) $name = '';
					}
				}
				$height = $point_y + 2;
				$pdf->SetXY($x, $height + 31 + 70.5);
				$pdf->MultiCell(28, 5, $name, 0, 'R');

			}

			// 21算出償却額 (19)×(20)
			$name = "";
			if(isset($data['method'])){
				if($data['method'] == '旧定額法'){
					if($data['data16'] > $data['data18']){
						$name = number_format(h($data['limited_depreciation_sum']));
						if($name == 0) $name = '';
					}
				}
			} else if(isset($data['data21'])) {
				$name =  number_format(h($data['data21']));
				if($name == 0) $name = '';
			}
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 75.7);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 23((21)+(22))又は((16)-(18))
			$name = "";
			if(isset($data['method'])){
				if($data['method'] == '旧定額法'){
					if($data['data16'] > $data['data18']){
						$name = number_format(h($data['limited_depreciation_sum']));
						if($name == 0) $name = '';
					}
				}
			} else if(isset($data['data21'])) {
				$name =  number_format(h($data['data21']));
				if($name == 0) $name = '';
			}

			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 86.6);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 24算出償却額((18)-1円)×12/60
			$name = "";
			if(isset($data['method'])){
				if($data['method'] == '旧定額法'){
					if($data['data16'] <= $data['data18']){
						$name = number_format(h($data['limited_depreciation_sum']));
					}
				}
			} else if(isset($data['data24'])){
				$name =  number_format(h($data['data24']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 91.9);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 25定額法の償却額計算の基礎となる金額(9)
			$name = "";
			if(isset($data['data25'])){
				$name = number_format(h($data['data25']));
			} else if($data['method'] == '定額法'){
				$name = number_format(h($data['data9']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 98.2);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 26定額法の償却率
			$name = "";
			if(isset($data['rate'])){
				if($data['method'] == '定額法'){
					if(isset($data['rate_next'])){
						$name =  h($data['rate_next']);
					} else {
						$name =  h($data['rate']);
					}
				}
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 103.7);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 27算出償却額
			$name = "";
			if(isset($data['data27'])){
				$name =  number_format(h($data['data27']));
			} else if($data['method'] == '定額法'){
				$name =  number_format(h($data['limited_depreciation_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 109.1);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 29計　(27)+(28)
			$name = "";
			if(isset($data['data27'])){
				$name = number_format(h($data['data27']));
			} else if($data['method'] == '定額法'){
				$name = number_format(h($data['limited_depreciation_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 118.7);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 30当期分の普通償却限度額等
			$name = "";
			if(isset($data['data30'])){
				$name = number_format(h($data['data30']));
			} else {
				$name = number_format(h($data['limited_depreciation_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 124);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 34合計(30)+(32)+(33)
			$name = "";
			if(isset($data['data30'])){
				$name =  number_format(h($data['data30']));
			} else {
				$name =  number_format(h($data['limited_depreciation_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 146);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 35当期償却額
			$name = "";
			$name =  number_format(h($data['depreciation_sum']));
			if($name == 0) ;
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 151.4);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 36償却不足額
			$name = "";
			if(isset($data['data36'])){
				$name = number_format(h($data['data36']));
			} else {
				if(isset($data['shortfall'])){
					$name = number_format(h($data['shortfall']));
				}
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 156.2);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 37償却超過額
			$name = "";
			if(isset($data['data37'])){
				$name = number_format(h($data['data37']));
			}else if(isset($data['excess'])){
				$name = number_format(h($data['excess']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 162.3);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 38前期からの繰越額
			$name = "";
			if(isset($data['previous_excess_sum'])){
				$name = number_format(h($data['previous_excess_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 167.6);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 39当期損金認容額
			$name = "";
      if(isset($data['data39'])){
        $name = number_format(h($data['data39']));
      } else if(isset($data['upholding_shortfall_sum'])){
				$name = number_format(h($data['upholding_shortfall_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 172.9);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 40積立金取崩しによるもの
			$name = "";
			if(isset($data['data40'])){
				$name = number_format(h($data['data40']));
			} else if(isset($data['upholding_compression_sum'])){
				$name = number_format(h($data['upholding_compression_sum']));
			}
			if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 179.2);
			$pdf->MultiCell(28, 5, $name, 0, 'R');

			// 41差引合計翌期への繰越額
			$name = "";
            if(isset($data['data41total'])){
                $name = number_format(h($data['data41total']));
            } else if(isset($data['data41'])){
				$name = number_format(h($data['$data41']));
			}
		    if($name == 0) $name = '';
			$height = $point_y + 2;
			$pdf->SetXY($x, $height + 31 + 184.4);
			$pdf->MultiCell(28, 5, $name, 0, 'R');
			$x += 24;
		}

		return $pdf;
    }

    /**
     * 別表16⑵ 旧定率法又は定率法による減価償却資産の償却額の計算に関する明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules1602s($pdf, $font) {

      $model = ClassRegistry::init('FixedAsset');

      //事業年度で様式選択
      $term_info = $model->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules16_2_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules16_02.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules16_2.pdf');
      }

        $Term = ClassRegistry::init('Term');
        $datas = $model->findTotalfor1602(null,null);

        $point_start_y = 37;      // 出力開始位置起点(縦)
        $point_step = 15.5;          // 次の出力
        //$point_y = $height = 22.5;  // 出力開始位置(縦)
        $point_y = $height = 18;  // 出力開始位置(縦)

        $record_count = 0;
        $balance_sum = 0;

        //事業年度の月数を取得
        $pdf->SetFont($font, null, 5, true);
        $months = $model->getCurrentYear();
        $height = $point_y + 2;
        $pdf->SetXY(66.4, 132.8);
        $pdf->MultiCell(38, 5, $months, 0, 'L');

        $term_id = CakeSession::read('Auth.User.term_id');

        $pdf->SetFont($font, null, 12, true);

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
            )));

        $y1 = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
        $m1 = date('n',strtotime($term['Term']['account_beggining'])) ;
        $d1 = date('j',strtotime($term['Term']['account_beggining'])) ;

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(87, 12);
        $pdf->MultiCell(38, 5, $y1, 0, 'R');
        $pdf->SetXY(94, 12);
        $pdf->MultiCell(38, 5, $m1, 0, 'R');
        $pdf->SetXY(102, 12);
        $pdf->MultiCell(38, 5, $d1, 0, 'R');

        $y2 = date('Y',strtotime($term['Term']['account_end'])) -1988;
        $m2 = date('n',strtotime($term['Term']['account_end'])) ;
        $d2 = date('j',strtotime($term['Term']['account_end'])) ;

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(87, 17.5);
        $pdf->MultiCell(38, 5, $y2, 0, 'R');
        $pdf->SetXY(94, 17.5);
        $pdf->MultiCell(38, 5, $m2, 0, 'R');
        $pdf->SetXY(102, 17.5);
        $pdf->MultiCell(38, 5, $d2, 0, 'R');

        // 法人名
        $pdf->SetFont($font, null, 9, true);
        $user_name = CakeSession::read('Auth.User.name');
        $user_name = substr($user_name,0,84);
        $height = (mb_strwidth($user_name, 'utf8') <= 29) ? 14.5 : 12.5;

        if(mb_strwidth($user_name, 'utf8') > 56){
        }

        $pdf->SetXY(152.2, $height);
        $pdf->MultiCell(47, 5, $user_name, 0, 'L');
        $pdf->SetFont($font, null, 6.8, true);

        $x = 71;

        foreach ($datas as $key => $data) {
            $record_count++;

            if($record_count >  5) {
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $record_count=0;
                $x = 71;

                $pdf->SetFont($font, null, 10, true);
                $pdf->SetXY(87, 12);
                $pdf->MultiCell(38, 5, $y1, 0, 'R');
                $pdf->SetXY(94, 12);
                $pdf->MultiCell(38, 5, $m1, 0, 'R');
                $pdf->SetXY(102, 12);
                $pdf->MultiCell(38, 5, $d1, 0, 'R');
                $pdf->SetXY(87, 17.5);
                $pdf->MultiCell(38, 5, $y2, 0, 'R');
                $pdf->SetXY(94, 17.5);
                $pdf->MultiCell(38, 5, $m2, 0, 'R');
                $pdf->SetXY(102, 17.5);
                $pdf->MultiCell(38, 5, $d2, 0, 'R');
                $pdf->SetFont($font, null, 9, true);
                $user_name = CakeSession::read('Auth.User.name');;
                $height = (mb_strwidth($user_name, 'utf8') <= 29) ? 14.5 : 12.5;

                $pdf->SetXY(152.2, $height);
                $pdf->MultiCell(47, 5, $user_name, 0, 'L');
                //事業年度の月数を取得
                $pdf->SetFont($font, null, 5, true);
                $months = $model->getCurrentYear();
                $height = $point_y + 2;
                $pdf->SetXY(66.4, 147.5);
                $pdf->MultiCell(38, 5, $months, 0, 'L');

                $pdf->SetFont($font, null, 6.8, true);

            }

            // 1種類
            $name = "";
            $name = h($data['account_title']);
            $height = 24.5;
            $pdf->SetXY($x, $height);
            $pdf->MultiCell(38, 5, $name, 0, 'C');

            // 7取得価額又は製作価額
            $name = "";
            $name = h(number_format($data['beggining_carrying_value']));
            $name = h(number_format($data['cost']));
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 32);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 8圧縮記帳による積立金計上額
            $name = "";
            $name = h(number_format($data['compression_sum']));
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 5.5 );
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 9差引取得価額 (7)-(8)
            $name = "";
            $name = h(number_format($data['data9']));
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 10 );
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 13差引帳簿記載金額
            $name = "";
            $name = h(number_format($data['data13']));
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 29);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 14損金に計上した当期償却額
            /*
            $name = h(number_format($data['depreciation_sum']));
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 33.5);
            $pdf->MultiCell(28, 5, $name, 0, 'R');
            */

            // 15前期から繰り越した償却超過額
            /*
            $name = h(number_format($data['']));
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 39);
            //$pdf->MultiCell(28, 5, $name, 0, 'R');
            */

            // 16合計　(13)+(14)+(15)
            $name = "";
            $name = h(number_format($data['data16']));
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 42.6);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 18償却額計算の基礎となる金額
            $name = "";
            $name = h(number_format($data['data16']));
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 52);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 19旧定額法の償却額計
            /*
            if(isset($data['method'])){
            if($data['method'] == '旧定率法'){
            $name = h($data['data18']);
            }
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 58.5);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            }
            */

            // 20旧定率法の償却率
            /*
            if(isset($data['method'])){
            if($data['method'] == '旧定率法'){
            if($data['data16'] > $data['data18']){
            $name = h($data['rate']);
            }
            }
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 63.6);
            $pdf->MultiCell(28, 5, $name, 0, 'R');
            }
            */

            // 21算出償却額 (19)×(20)
            $name = "";
            if(isset($data['data21'])){

                $name = number_format(h($data['data21']));

            } else if(isset($data['method'])){
                if($data['method'] == '旧定率法'){
                    if($data['data16'] > $data['data18']){
                        $name = number_format(h($data['limited_depreciation_sum']));
                    }
                }
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 67);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 23((21)+(22))又は((18)-(19))
            $name="";
            if(isset($data['data21'])){

                $name = number_format(h($data['data21']));

            } else if(isset($data['method'])){

                if($data['method'] == '旧定率法'){
                    if($data['data16'] > $data['data18']){
                        $name = number_format(h($data['limited_depreciation_sum']));
                    }
                }
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 76.6);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 24算出償却額((18)-1円)×12/60
            $name = "";
            if(isset($data['method'])){
                if($data['method'] == '旧定率法'){
                    if($data['data16'] <= $data['data18']){
                        $name = number_format(h($data['limited_depreciation_sum']));
                    }
                }
            } else if(isset($data['data24'])){
                $name =  number_format(h($data['data24']));
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 82.2);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 26調整前償却額　(18)×(25)
            $name = "";

            if(isset($data['data26total'])){
                $name = number_format(h($data['data26total']));
            } else if(isset($data['data26'])){
                if($data['period'] !=12){
                    $name =  '(' . number_format(h($data['data26'])).')';
                }
                $name  = number_format(h($data['chouseimaesyoukyakugaku']));

            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 91.5);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 28償却保証額　(9)×(27)
            $name = "";
            if(isset($data['data28total'])){
                $name = number_format(h($data['data28total']));
            }else if(isset($data['data28'])){
                $name =  number_format(h($data['data28']));
            }

            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 101.1);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 29改定取得価額計
            $name="";
            if(isset($data['data29total'])){
                if($data['data29total']>0){
                    $name =  number_format(h($data['data29total']));
                }
            }elseif(isset($data['kaitei_cost'])){
                $name =  $data['kaitei_cost'];
            }

            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 106);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 30当期分の普通償却限度額等
            /*
            $name = "";
            if(isset($data['data30'])){
            $name = number_format(h($data['data30']));
            } else {
            $name = number_format(h($data['limited_depreciation_sum']));
            }
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 124);
            $pdf->MultiCell(28, 5, $name, 0, 'R');
            */

            // 31改定償却額
            $name = "";
            if(isset($data['data31total'])){
                if($data['data31total']>0){
                    $name =  number_format(h($data['data31total']));
                }
            }else if(isset($data['kaiteishoukyakugaku'])){
                $name = $data['kaiteishoukyakugaku'];
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 115.7);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 33計　((26)又は(31))+(32)
            $name = "";
            if(isset($data['data33total'])){
                $name = number_format(h($data['data33total']));
            }else if(isset($data['method'])){
                if($data['method'] == '定率法'){
                    $name = number_format(h($data['limited_depreciation_sum']));
                }
            }
            //if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 126);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 34当期分の普通償却限度額
            $name = "";
            if(isset($data['data34total'])){
                $name =  number_format(h($data['data34total']));
            } else if(isset($data['limited_depreciation_sum'])){
                $name =  number_format(h($data['limited_depreciation_sum']));
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 131);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 38合計(34)+(36)+(37)
            $name = "";
            if(isset($data['data34total'])){
                $name =  number_format(h($data['data34total']));
            } else if(isset($data['limited_depreciation_sum'])){
                $name =  number_format(h($data['limited_depreciation_sum']));
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 153);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 39当期償却額
            $name = "";
            if(isset($data['data39total'])){
                $name = number_format(h($data['data39total']));
            }else if (isset($data['depreciation_sum'])) {

                $name =  number_format(h($data['depreciation_sum']));
            }
            if($name == 0) ;
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 158.1);
            $pdf->MultiCell(28, 5, $name, 0, 'R');


            // 40償却不足額　(38)-(39)
            $name = "";
            if(isset($data['data40total'])){
                if($data['data40total']>0){
                    $name = number_format(h($data['data40total']));
                }
            } else if(isset($data['shortfall'])){
                $name = number_format(h($data['shortfall']));
            }
            if($name == 0) $name = '';
            $height = $point_y + 2;
            $pdf->SetXY($x, $height + 31 + 163.1);
            $pdf->MultiCell(28, 5, $name, 0, 'R');

            // 41償却超過額
            $name = "";
            if(isset($data['data41total'])){
                if($data['data41total']>0){
                    $name =  number_format(h($data['data41total']));
                }
            }else if(isset($data['excess'])){
                $name = number_format(h($data['excess']));}

                if($name == 0) $name = '';
                $height = $point_y + 2;
                $pdf->SetXY($x, $height + 31 + 168.1);
                $pdf->MultiCell(28, 5, $name, 0, 'R');

                // 42前期からの繰越額
                $name = "";
                if(isset($data['data42total'])){
                    if($data['data42total']>0){
                        $name = number_format(h($data['data42total']));
                    }
                }else if(isset($data['previous_excess_sum'])){
                    $name = number_format(h($data['previous_excess_sum']));
                }
                if($name == 0) $name = '';
                $height = $point_y + 2;
                $pdf->SetXY($x, $height + 31 + 172.7);
                $pdf->MultiCell(28, 5, $name, 0, 'R');

                // 43当期損金認容額
                $name = "";
                if(isset($data['data43total'])){
                    if($data['data43total']>0){
                        $name = number_format(h($data['data43total']));
                    }
                }else if(isset($data['upholding_shortfall_sum'])){
                    $name = number_format(h($data['upholding_shortfall_sum']));
                }
                if($name == 0) $name = '';
                $height = $point_y + 2;
                $pdf->SetXY($x, $height + 31 + 177.9);
                $pdf->MultiCell(28, 5, $name, 0, 'R');

                // 44積立金取崩しによるもの
                $name = "";
                if(isset($data['data44total'])){
                    $name = number_format(h($data['data44total']));
                } else if(isset($data['upholding_compression_sum'])){
                    $name = number_format(h($data['upholding_compression_sum']));
                }
                if($name == 0) $name = '';
                $height = $point_y + 2;
                $pdf->SetXY($x, $height + 31 + 182.3);
                $pdf->MultiCell(28, 5, $name, 0, 'R');

                // 45積立金取崩しによるもの
                $name = "";
                if(isset($data['data45total'])){
                    if($data['data45total']>0){
                        $name = number_format(h($data['data45total']));
                    }
                }else if(isset($data['data45'])){
                    $name = number_format(h($data['data45']));
                }
                if($name == 0) $name = '';
                $height = $point_y + 2;
                $pdf->SetXY($x, $height + 31 + 187.4);
                $pdf->MultiCell(28, 5, $name, 0, 'R');
                $x += 24;
        }

        return $pdf;
    }

    /**
     * 預貯金等の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_deposits($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'deposit_detail.pdf');

        $model = ClassRegistry::init('Deposit');
        $deposits = $model->findForPDF();

        $last_page = count($deposits) - 1;
        foreach ($deposits as $page_number => $page) {
            $pdf->useTemplate($template, null, null, null, null, true);

            // ページ内描画
            /**
            * MultiCell詳細
            * 横幅、最小の高さ、テキスト、境界線、テキスト位置
            * @link http://www.t-net.ne.jp/~cyfis/tcpdf/tcpdf/MultiCell.html
            */
            foreach ($page as $key => $deposit) {
                if ($key === 'balance') {
                    //ページごとの計
                    $pdf->SetFont($font, null, 9, true); //フォントサイズ変更

                    $n1 = substr($deposit, 0, -6); //百万
                    $n2 = substr($deposit, -6, -3);//千
                    $n3 = substr($deposit, -3);    //円

                    $pdf->SetXY(115, 242);
                    $pdf->MultiCell(12, 5, h($n1), 0, 'R');
                    $pdf->SetXY(129.5, 242);
                    $pdf->MultiCell(7, 5, h($n2), 0, 'C');
                    $pdf->SetXY(138.5, 242);
                    $pdf->MultiCell(7, 5, h($n3), 0, 'L');

                    $pdf->SetFont($font, null, 7, true); //フォントサイズ変更
                }


                //金融機関名
                $bank_name = mb_strimwidth(preg_replace('/(銀行)$/', '', $deposit['Deposit']['bank_name']), 0, 24, '', 'utf8');
                if (mb_strwidth($bank_name, 'utf8') <= 12) {
                    $height = 26;
                } else {
                    $height = 24.5;
                }
                $pdf->SetXY(27, $height + 9 * ($key + 1));
                $pdf->MultiCell(17, 5, h($bank_name), 0, 'C');

                //支店名
                $branch_name = mb_strimwidth(preg_replace('/(支店)$/', '', $deposit['Deposit']['branch_name']), 0, 24, '', 'utf8');
                if (mb_strwidth($branch_name, 'utf8') <= 12) {
                    $height = 26;
                } else {
                    $height = 24.5;
                }
                $pdf->SetXY(48, $height + 9 * ($key + 1));
                $pdf->MultiCell(17, 5, h($branch_name), 0, 'C');

                //種類
                $account_title = preg_replace('/(預金)$/', '', $deposit['AccountTitle']['account_title']);
                $pdf->SetXY(67, 26 + 9 * ($key + 1));
                $pdf->MultiCell(19, 5, h($account_title), 0, 'C');

                //口座番号
                if (mb_strwidth($deposit['Deposit']['number'], 'utf8') <= 18) {
                    $height = 26;
                } else {
                    $height = 24.5;
                }
                $pdf->SetXY(89, $height + 9 * ($key + 1));
                $pdf->MultiCell(25, 5, h($deposit['Deposit']['number']), 0, 'C');

                // 期末現在高
                $pdf->SetFont($font, null, 9, true); //フォントサイズ変更

                $n1 = substr($deposit['Deposit']['balance'], 0, -6); //百万
                $n2 = substr($deposit['Deposit']['balance'], -6, -3);//千
                $n3 = substr($deposit['Deposit']['balance'], -3);    //円

                $pdf->SetXY(115, 26 + 9 * ($key + 1));
                $pdf->MultiCell(12, 5, h($n1), 0, 'R');
                $pdf->SetXY(129.5, 26 + 9 * ($key + 1));
                $pdf->MultiCell(7, 5, h($n2), 0, 'R');
                $pdf->SetXY(138.5, 26 + 9 * ($key + 1));
                $pdf->MultiCell(7, 5, h($n3), 0, 'R');

                $pdf->SetFont($font, null, 7, true); //フォントサイズ変更

                //摘要
                if (mb_strwidth($deposit['Deposit']['note'], 'utf8') <= 30) {
                    $height = 26;
                } else {
                    $height = 24.5;
                }
                $pdf->SetXY(150, $height + 9 * ($key + 1));
                $pdf->MultiCell(41, 5, h($deposit['Deposit']['note']), 0, 'L');
            }

            if ($page_number != $last_page) {
                //最後のページではない限りページ追加
                $pdf->AddPage();
            }
        }

        return $pdf;

    }

    /**
     * 受取手形の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_notes_receivales($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'notes_receivables.pdf');

        $model = ClassRegistry::init('NotesReceivable');
        $noteReceivables = $model->findPdfExportData();

        $point_start_y = 33;      // 出力開始位置起点(縦)
        $point_step    = 8.62;          // 次の出力
        $second_step   = 8.62;
        $point_y       = $second_y = 33;
        $height        = 35;  // 出力開始位置(縦)
        $receiver_x    = 26.9;            // 科目名の表示位置
        $draw_x        = 58;             // 名称の表示位置
        $pay_bank_x    = 77.6;          // 所在地の表示位置
        $sum_x         = 84.5;         // 期末現在高の表示位置
        $discount_x    = 133;
        $note_x        = 157;            // 摘要の表示位置
        $total_x       = 98;

        $record_count = 0;

        $total        = 0;

        foreach ($noteReceivables as $data) {

            $record_count++;
            $pdf->SetFont($font, null, 8, true);

            // Name
            $receiver = h($data['NotesReceivable']['name']);
            $height   = (mb_strwidth($receiver, 'utf8') <= 22) ? $point_y + 2 : $point_y + 0.4;
            $pdf->SetXY($receiver_x, $height);
            $pdf->MultiCell(35, 5, $receiver, 0, 'L');

            $pdf->SetFont($font, null, 7, true);
            // Draw date
            $draw_date   = $data['NotesReceivable']['draw_date'];
            if ($draw_date) {
                $height = $second_y;
                $this->putHeiseiDate($pdf, $height, $draw_x, $draw_date, array(0, -1.2, -3));
            }

            // Pay date
            $pay_date   = $data['NotesReceivable']['pay_date'];
            if ($pay_date) {
                $height = $second_y+4.4;
                $this->putHeiseiDate($pdf, $height, $draw_x, $pay_date, array(0, -1.2, -3));
            }

            // Pay bank
            $pay_bank = h($data['NotesReceivable']['pay_bank']);
            $height = $point_y-0.5;
            $pdf->SetXY($pay_bank_x, $height);
            $pdf->MultiCell(12, 5, $pay_bank, 0, 'L');

            // Pay branch
            $_pay_branch = h($data['NotesReceivable']['pay_branch']);
            $pay_branch = substr_replace($_pay_branch, PHP_EOL, 9, 0);
            $height = $point_y+1.8;
            $pdf->SetXY($pay_bank_x+8.7, $height);
            $pdf->MultiCell(12, 5, $pay_branch, 0, 'R');

            $pdf->SetFont($font, null, 9, true);
            // Sum
            $sum = $data['NotesReceivable']['sum'];
            $total += $sum;
            $height = $point_y + 3.2;
            $n1 = substr($sum, 0, -6); //百万
            $n2 = substr($sum, -6, -3);//千
            $n3 = substr($sum, -3);    //円
            $pdf->SetXY($sum_x, $height);
            $pdf->MultiCell(25, 5, $n1, 0, 'R');
            $pdf->SetXY($sum_x+14.3, $height);
            $pdf->MultiCell(20, 5, $n2, 0, 'R');
            $pdf->SetXY($sum_x+37, $height);
            $pdf->MultiCell(25, 5, $n3, 0, 'L');

            $pdf->SetFont($font, null, 7, true);
            // Discount bank
            $discount_bank = h($data['NotesReceivable']['discount_bank']);
            $height = $point_y-0.5;
            $pdf->SetXY($discount_x-0.5, $height);
            $pdf->MultiCell(12, 5, $discount_bank, 0, 'L');

            // Discount branch
            $_discount_branch = h($data['NotesReceivable']['discount_branch']);
            $discount_branch = substr_replace($_discount_branch, PHP_EOL, 9, 0);
            $height = $point_y+1.8;
            $pdf->SetXY($discount_x+12, $height);
            $pdf->MultiCell(12, 5, $discount_branch, 0, 'R');

            // Note
            $note = h($data['NotesReceivable']['note']);
            $height   = (mb_strwidth($note, 'utf8') <= 28) ? $point_y + 2 : $point_y + 0.8;
            $pdf->SetXY($note_x-0.8, $height);
            $pdf->MultiCell(38, 5, $note, 0, 'L');

            if (0 < ($record_count % Configure::read('NOTE_PAYABLE_PDF_ROW'))) {
                $point_y += $point_step;
                $second_y += $second_step;
            } else {
                // 計
                $height = $point_start_y + ($point_step * Configure::read('NOTE_PAYABLE_PDF_ROW')) + 1.7;
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $height, $total_x, $total, array(0, 0.7, 2.5));
                $total = 0;

                // ページ追加
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $point_y = $point_start_y;
                $second_y = $point_start_y;
            }
        }

        if (0 < ($record_count % Configure::read('NOTE_PAYABLE_PDF_ROW'))) {
            // 計
            $height = $point_start_y + ($point_step * Configure::read('NOTE_PAYABLE_PDF_ROW')) + 1.7;
            $pdf->SetFont($font, null, 9, true);
            $this->putPricePdf($pdf, $height, $total_x, $total, array(0, 0.7, 2.5));
        }

        return $pdf;
    }

    /**
     * 売掛金（未収入金）の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_accounts_receivales($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'accounts_receivable.pdf');

        $model = ClassRegistry::init('AccountsReceivable');
        $accountReceivables = $model->findPdfExportData();

        $point_start_y = 37;      // 出力開始位置起点(縦)
        $point_step = 8;          // 次の出力
        $point_y = $height = 37;  // 出力開始位置(縦)
        $title_x = 28;            // 科目名の表示位置
        $name_x = 45;             // 名称の表示位置
        $address_x = 83;          // 所在地の表示位置
        $balance_x = 135;         // 期末現在高の表示位置
        $note_x = 170;            // 摘要の表示位置
        $subtotal_y = $point_start_y + ($point_step * Configure::read('ACCOUNT_RECEIVABLE_PDF_ROW')) + 2;

        $balance_margin = array(0, 1.5, 2.5);
        $record_count = 0;
        $balance_sum = 0;
        $current_page = 1;
        $end_page = ceil(count($accountReceivables) / Configure::read('ACCOUNT_RECEIVABLE_PDF_ROW'));

        foreach ($accountReceivables as $data) {

            $record_count++;
            $pdf->SetFont($font, null, 7, true);

            // 科目
            $account_title = h($data['AccountTitle']['account_title']);
            $height = (mb_strwidth($account_title, 'utf8') <= 12) ? $point_y + 2 : $point_y;
            $pdf->SetXY($title_x, $height);
            $pdf->MultiCell(18, 5, $account_title, 0, 'C');

            // 名称
            $name = h($data['NameList']['name']);
            $name = (mb_strlen($name, 'utf-8') <= 28) ? $name : mb_substr($name, 0, 27, 'utf-8').'...';
            $height = (mb_strwidth($name, 'utf8') <= 29) ? $point_y + 2 : $point_y;
            $pdf->SetXY(45.5, $height);
            $pdf->MultiCell(38, 5, $name, 0, 'L');


            // 所在地
            $address = h($data['NameList']['prefecture'] . $data['NameList']['city'] . $data['NameList']['address']);
            $pdf->SetFont($font, null, 6, true);
            $height = (mb_strwidth($address, 'utf8') <= 46) ? $point_y + 2 : $point_y;
            $pdf->SetXY($address_x, $height);
            $pdf->MultiCell(51, 5, $address, 0, 'L');

            // 期末現在高
            $balance = $data['AccountsReceivable']['balance'];
            $balance_sum += $balance;
            $pdf->SetFont($font, null, 9, true);
            $height = $point_y + 2;
            $this->putPricePdf($pdf, $height, $balance_x, $balance, $balance_margin);

            // 摘要
            $note = h($data['AccountsReceivable']['note']);
            $pdf->SetFont($font, null, 7, true);
            $height = (mb_strwidth($note, 'utf8') <= 17) ? $point_y + 2 : $point_y;
            $pdf->SetXY($note_x, $height);
            $pdf->MultiCell(23, 5, $note, 0, 'L');

            if (0 < ($record_count % Configure::read('ACCOUNT_RECEIVABLE_PDF_ROW'))) {
                $point_y += $point_step;
            } else {
                // 計
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $subtotal_y, $balance_x, $balance_sum, $balance_margin);
                $balance_sum = 0;

                if ($current_page < $end_page) {
                    // ページ追加
                    $pdf->AddPage();
                    $pdf->useTemplate($template, null, null, null, null, true);
                    $point_y = $point_start_y;
                    $current_page++;
                }
            }
        }

        if (0 < ($record_count % Configure::read('ACCOUNT_RECEIVABLE_PDF_ROW'))) {
            // 計
            $pdf->SetFont($font, null, 9, true);
            $this->putPricePdf($pdf, $subtotal_y, $balance_x, $balance_sum, $balance_margin);
        }

        return $pdf;

    }

    /**
     * 仮払金（前渡金）の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_suspense_payments($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'suspense_payment.pdf');

        $SuspensePayment = ClassRegistry::init('SuspensePayment');
        $Loan = ClassRegistry::init('Loan');
        $suspensePayments = $SuspensePayment->findPdfExportData();
        $loans = $Loan->findPdfExportData();

        // 1ページ出力分ごとに分割
        $chunkSuspensePayments = array_chunk($suspensePayments, Configure::read('SUSPENSE_PAYMENT_PDF_ROW'));
        $chunkLoans = array_chunk($loans, Configure::read('LOAN_PDF_ROW'));

        $end_page = max(count($chunkSuspensePayments), count($chunkLoans));

        for ($current_page = 1; $current_page <= $end_page; $current_page++) {

            // 仮払金出力
            if (isset($chunkSuspensePayments[$current_page - 1])) {
                $this->_putSuspensePaymentPDF($pdf, $font, $chunkSuspensePayments[$current_page - 1]);
            }
            // 貸付金出力
            if (isset($chunkLoans[$current_page - 1])) {
                $this->_putLoanPDF($pdf, $font, $chunkLoans[$current_page - 1]);
            }

            if ($current_page < $end_page) {
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
            }

        }

        return $pdf;

    }

    /**
     * 仮払金出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS OBJ $font
     * @param array $suspensePayments
     */
    function _putSuspensePaymentPDF(&$pdf, $font, $suspensePayments) {

        $point_start_y = 35;      // 出力開始位置起点(縦)
        $point_step = 7.4;          // 次の出力
        $point_y = $height = 35;  // 出力開始位置(縦)
        $title_x = 28;              // 科目名の表示位置
        $name_x = 52;               // 名称の表示位置
        $address_x = 82.5;            // 所在地の表示位置
        $relationship_x = 126;      // 関係の表示位置
        $balance_x = 138;           // 期末現在高の表示位置
        $note_x = 170;              // 取引の内容の表示位置

        $line_margin = 1.9;

        foreach ($suspensePayments as $key => $data) {

            // 科目
            $pdf->SetFont($font, null, 7, true);
            $account_title = h($data['AccountTitle']['account_title']);
            $height = $point_y + $line_margin;
            $pdf->SetXY($title_x, $height);
            $pdf->MultiCell(22, 5, $account_title, 0, 'C');

            // 名称
            $name = h($data['NameList']['name']);
            $name = (mb_strlen($name, 'utf-8') <= 20) ? $name : mb_substr($name, 0, 19, 'utf-8').'...';
            $height = (mb_strwidth($name, 'utf8') <= 20) ? $point_y + $line_margin : $point_y;
            $pdf->SetXY($name_x, $height);
            $pdf->MultiCell(29, 5, $name, 0, 'L');

            // 所在地
            $address = h($data['NameList']['prefecture'] . $data['NameList']['city'] . $data['NameList']['address']);
            $address = (mb_strlen($address, 'utf-8') <= 33) ? $address : mb_substr($address, 0, 32, 'utf-8').'...';
            $height = (mb_strwidth($address, 'utf8') <= 33) ? $point_y + $line_margin : $point_y;
            $pdf->SetXY($address_x, $height);
            $pdf->MultiCell(45, 5, $address, 0, 'L');

            // 関係
            $relationship = h($data['NameList']['relationship']);
            $height = (mb_strwidth($relationship, 'utf8') <= 8) ? $point_y + $line_margin : $point_y;
            $pdf->SetXY($relationship_x, $height);
            $pdf->MultiCell(13, 5, $relationship, 0, 'C');

            // 期末現在高
            $pdf->SetFont($font, null, 8, true);
            $balance = $data['SuspensePayment']['balance'];
            $height = $point_y + $line_margin;
            $height += ($key === 0) ? 0.6 : 0;
            $this->putPricePdf($pdf, $height, $balance_x, $balance);

            // 取引の内容
            $pdf->SetFont($font, null, 7, true);
            $note = h($data['SuspensePayment']['note']);
            $height = (mb_strwidth($note, 'utf8') <= 17) ? $point_y + $line_margin : $point_y;
            $pdf->SetXY($note_x, $height);
            $pdf->MultiCell(23, 5, $note, 0, 'L');

            $point_y += $point_step;
        }

    }

    /**
     * 貸付金出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS OBJ $font
     * @param array $loans
     */
    function _putLoanPDF(&$pdf, $font, $loans) {

        $point_start_y = 152.3;     // 出力開始位置起点(縦)
        $point_step = 11.53;        // 次の出力
        $point_y = $height = 152.3; // 出力開始位置(縦)
        $name_x = 28;               // 名称の表示位置
        $address_x = 28;            // 所在地の表示位置
        $relationship_x = 65.2;     // 関係の表示位置
        $balance_x = 83.3;          // 期末現在高の表示位置
        $interest_sum_x = 113.5;    // 期中の受取利息額の表示位置
        $rate_x = 113.5;            // 利率の表示位置
        $reason_x = 134.3;          // 貸付理由の表示位置
        $collateral_x = 157.3;      // 担保の内容の表示

        $line_margin = 1.2;
        $second_line_y = 5.8;
        $middle_line_y = 3.7;
        $balance_y = 5;
        $balance_margin = array(0,0.3,0.7);

        $balance_subtotal = $interest_subtotal = 0;

        foreach ($loans as $key => $data) {

            // 名称
            $pdf->SetFont($font, null, 7, true);
            $name = h($data['NameList']['name']);
            if ($name) {
                if (30 < mb_strwidth($name, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $name = $this->roundLineStrByWidth($name, 34);
                $height = (mb_strwidth($name, 'utf8') <= 34) ? $point_y + $line_margin : $point_y;
                $pdf->SetXY($name_x, $height);
                $pdf->MultiCell(40, 5, $name, 0, 'L');
            }

            // 所在地
            $pdf->SetFont($font, null, 7, true);
            $address = h($data['NameList']['prefecture'] . $data['NameList']['city'] . $data['NameList']['address']);
            if ($address) {
                if (45 < mb_strwidth($address, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $address = $this->roundLineStrByWidth($address, 51);
                $height = (mb_strwidth($address, 'utf8') <= 51) ? $point_y + $line_margin : $point_y;
                $pdf->SetXY($address_x, $height + $second_line_y);
                $pdf->MultiCell(58, 5, $address, 0, 'L');
            }

            // 関係
            $pdf->SetFont($font, null, 7, true);
            $relationship = h($data['NameList']['relationship']);
            if ($relationship) {
                if (14 < mb_strwidth($relationship, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $relationship = $this->roundLineStrByWidth($relationship, 16);
                $height = (mb_strwidth($relationship, 'utf8') <= 16) ? $point_y + $line_margin : $point_y;
                $pdf->SetXY($relationship_x, $height);
                $pdf->MultiCell(20, 5, $relationship, 0, 'C');
            }

            // 期末現在高
            $pdf->SetFont($font, null, 8, true);
            $balance = $data['Loan']['balance'];
            $height = $point_y + $balance_y;
            $this->putPricePdf($pdf, $height, $balance_x, $balance, $balance_margin);

            // 期中の受取利息額
            $pdf->SetFont($font, null, 7, true);
            $interest_sum = $data['Loan']['interest_sum'];
            $height = ($key === 0)? $point_y + 2.2 : $point_y + $line_margin;
            $pdf->SetXY($interest_sum_x, $height);
            $pdf->MultiCell(20, 5, number_format($interest_sum), 0, 'R');

            // 利率
            $pdf->SetFont($font, null, 7, true);
            $rate = $data['Loan']['rate'];
            if ($rate) {
                $height = $point_y + $line_margin;
                $pdf->SetXY($rate_x, $height + $second_line_y);
                $pdf->MultiCell(20, 5, $rate.'%', 0, 'R');
            }

            // 貸付理由
            $pdf->SetFont($font, null, 7, true);
            $reason = h($data['Loan']['reason']);
            if ($reason) {
                $reason = $this->roundLineStrByWidth($reason, 18, 3);
                $row = ceil(mb_strwidth(str_replace("\n",'', $reason), 'utf8') / 18);  // 行数
                $height = $point_y + ($middle_line_y - (($row-1) * 1.4));
                $pdf->SetXY($reason_x, $height);
                $pdf->MultiCell(26, 3, $reason, 0, 'L');
            }

            // 担保の内容
            $pdf->SetFont($font, null, 7, true);
            $collateral = h($data['Loan']['collateral']);
            if ($collateral) {
                $collateral = $this->roundLineStrByWidth($collateral, 28, 3);
                $row = ceil(mb_strwidth(str_replace("\n",'', $collateral), 'utf8') / 28);  // 行数
                $height = $point_y + ($middle_line_y - (($row-1) * 1.4));
                $pdf->SetXY($collateral_x, $height);
                $pdf->MultiCell(38, 3, $collateral, 0, 'L');
            }

            $point_y += $point_step;
            $balance_subtotal += $data['Loan']['balance'];
            $interest_subtotal += $data['Loan']['interest_sum'];

        }

        // 計表示
        $pdf->SetFont($font, null, 8, true);
        $height = $point_start_y + ($point_step * Configure::read('LOAN_PDF_ROW')) + $middle_line_y;
        $this->putPricePdf($pdf, $height, $balance_x, $balance_subtotal, $balance_margin);

        $pdf->SetFont($font, null, 7, true);
        $height = $point_start_y + ($point_step * Configure::read('LOAN_PDF_ROW')) + $line_margin;
        $pdf->SetXY($interest_sum_x + 1.5, $height);
        $pdf->MultiCell(20, 5, number_format($interest_subtotal), 0, 'C');

    }

    /**
     * 棚卸資産(商品又は製品、半製品、仕掛品、原材料、貯蔵品)の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_inventories($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'inventory.pdf');

        $Inventory = ClassRegistry::init('Inventory');
        $AccountInfo = ClassRegistry::init('AccountInfo');

        $user_id = CakeSession::read('Auth.User.id');
        $term_id = CakeSession::read('Auth.User.term_id');

        $inventories = $Inventory->findPdfExportData();
        $accountInfo = $AccountInfo->findAccountInfo($user_id, $term_id);

        $point_start_y = 33;      // 出力開始位置起点(縦)
        $point_step    = 7.82;          // 次の出力
        $point_y       = 33;
        $height        = 35;  // 出力開始位置(縦)
        $subject_x     = 24.5;            // 科目名の表示位置
        $item_x        = 50.2;             // 名称の表示位置
        $quantity_x    = 84.2;          // 所在地の表示位置
        $unitprice_x   = 103.5;         // 期末現在高の表示位置
        $sum_x         = 123;
        $note_x        = 157.7;            // 摘要の表示位置
        $total_x       = 123.3;
        $invdate_x     = 77;

        $record_count = 0;
        $total        = 0;
        $balance_margin = array(0, 1.35, 2.5);

        $subjectArr = array(
            14 => '商品',
            15 => '製品',
            16 => '原材料',
            17 => '仕掛品',
            19 => '貯蔵品',
            18 => '未成工事支出金',
            395 => '半製品',
        );

        foreach ($inventories as $key => $data) {
            $record_count++;
            $pdf->SetFont($font, null, 8, true);

            // Subject
            $subject = h($subjectArr[$data['Inventory']['account_title_id']]);
            $height   = $point_y + 2;
            $pdf->SetXY($subject_x, $height);
            $pdf->MultiCell(30, 5, $subject, 0, 'C');

            // Item
            $item = h($data['Inventory']['item']);
            $height   = (mb_strwidth($item, 'utf8') <= 23) ? $point_y + 2.2 : $point_y - 0.8;
            $pdf->SetXY($item_x, $height);
            $pdf->MultiCell(35, 5, $item, 0, 'L');

            // Note
            $note = h($data['Inventory']['note']);
            $height   = (mb_strwidth($note, 'utf8') <= 26) ? $point_y + 2.1 : $point_y - 1;
            $pdf->SetXY($note_x, $height);
            $pdf->MultiCell(35, 5, $note, 0, 'C');

            $pdf->SetFont($font, null, 9, true);

            // Quantity
            $quantity = ($data['Inventory']['num']) ? number_format($data['Inventory']['num']) : '';
            $height   = $point_y + 2.1;
            $pdf->SetXY($quantity_x, $height);
            $pdf->MultiCell(20, 5, $quantity, 0, 'C');

            // Unit price
            $unitprice = ($data['Inventory']['unit_price']) ? number_format($data['Inventory']['unit_price']) : '';
            $height   = $point_y + 2.1;
            $pdf->SetXY($unitprice_x, $height);
            $pdf->MultiCell(20, 5, $unitprice, 0, 'C');

            // Balance
            $balance = $data['Inventory']['balance'];
            $total += $balance;
            $height = $point_y + 2.1;
            $this->putPricePdf($pdf, $height, $sum_x, $balance, $balance_margin);

            if (0 < ($record_count % Configure::read('INVENTORY_PDF_ROW'))) {
                $point_y += $point_step;
            } else {
                // 計
                $height = $point_start_y + ($point_step * Configure::read('INVENTORY_PDF_ROW')) + 1.3;
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $height, $total_x, $total, $balance_margin);
                $total = 0;

                // Inventory date
                $height = $point_start_y + ($point_step * Configure::read('INVENTORY_PDF_ROW')) + 26.5;
                $pdf->SetFont($font, null, 7, true);
                $this->_putInventoryDate($pdf, $font, $height, $invdate_x, $accountInfo);

                // ページ追加
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $point_y = $point_start_y;
            }
        }

        if (0 < ($record_count % Configure::read('INVENTORY_PDF_ROW'))) {
            // 計
            $height = $point_start_y + ($point_step * Configure::read('INVENTORY_PDF_ROW')) + 1.3;
            $pdf->SetFont($font, null, 9, true);
            $this->putPricePdf($pdf, $height, $total_x, $total, $balance_margin);

            // Inventory date
            $height = $point_start_y + ($point_step * Configure::read('INVENTORY_PDF_ROW')) + 26.5;
            $pdf->SetFont($font, null, 7, true);
            $this->_putInventoryDate($pdf, $font, $height, $invdate_x, $accountInfo);
        }

        return $pdf;

    }

    /**
     * 期末棚卸し方法出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param int $height
     * @param int $invdate_x
     * @param array $accountInfo
     */
    function _putInventoryDate(&$pdf, $font, $height, $invdate_x, $accountInfo)
    {
        $this->putHeiseiDate($pdf, $height, $invdate_x + 13.5, $accountInfo['AccountInfo']['inventry_date'], array(0, 5, 8.5));

        // Mark
        switch ($accountInfo['AccountInfo']['inventry_method']) {
            case 'A':
                $height = $height - 8;
                break;
            case 'B':
                $height = $height - 4.4;
                break;
            case 'C':
                $height = $height - 0.7;
                break;
            default:
                break;
        }
        $pdf->SetFont($font, null, 13, true);
        $pdf->SetXY($invdate_x-29.5, $height);
        $pdf->MultiCell(20, 5, '◯', 0, 'L');
    }

    /**
     * 支払手形の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_notes_payables($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'notes_payables.pdf');

        $NotesPayable = ClassRegistry::init('NotesPayable');
        $notePayables = $NotesPayable->findPdfExportData();

        $point_start_y = 33;      // 出力開始位置起点(縦)
        $point_step    = 9.23;          // 次の出力
        $note_step     = 9.23;
        $pay_step      = 9.23;
        $point_y       = $note_y = $pay_y = 33;
        $height        = 35;  // 出力開始位置(縦)
        $receiver_x    = 27.5;            // 科目名の表示位置
        $draw_x        = 59.5;             // 名称の表示位置
        $pay_bank_x    = 79;          // 所在地の表示位置
        $sum_x         = 99.2;         // 期末現在高の表示位置
        $note_x        = 135;            // 摘要の表示位置
        $total_x       = 99.2;

        $draw_margin = array(0, -1.3, -3);
        $price_margin = array(0, 1, 2.5);

        $record_count = 0;

        $total        = 0;

        foreach ($notePayables as $data) {

            $record_count++;
            $pdf->SetFont($font, null, 8, true);

            // Name
            $receiver = h($data['NotesPayable']['name']);
            $height   = (mb_strwidth($receiver, 'utf8') <= 22) ? $point_y + 2.6 : $point_y + 0.9;
            $pdf->SetXY($receiver_x, $height);
            $pdf->MultiCell(35, 5, $receiver, 0, 'L');

            $pdf->SetFont($font, null, 7, true);
            // Draw date
            $draw_date   = $data['NotesPayable']['draw_date'];
            if ($draw_date) {
                $height = $pay_y+0.45;
                $this->putHeiseiDate($pdf, $height, $draw_x, $draw_date, $draw_margin);
            }

            // Pay date
            $pay_date   = $data['NotesPayable']['pay_date'];
            if ($pay_date) {
                $height = $pay_y+5.1;
                $this->putHeiseiDate($pdf, $height, $draw_x, $pay_date, $draw_margin);
            }

            // Pay bank
            $pay_bank = h($data['NotesPayable']['pay_bank']);
            $height = $pay_y;
            $pdf->SetXY($pay_bank_x+0.2, $height);
            $pdf->MultiCell(12, 5, $pay_bank, 0, 'L');

            // Pay branch
            $_pay_branch = h($data['NotesPayable']['pay_branch']);
            $pay_branch = substr_replace($_pay_branch, PHP_EOL, 9, 0);
            $height = $pay_y+2.4;
            $pdf->SetXY($pay_bank_x+8.3, $height);
            $pdf->MultiCell(12, 5, $pay_branch, 0, 'R');

            $pdf->SetFont($font, null, 9, true);
            // Sum
            $sum = $data['NotesPayable']['sum'];
            $total += $sum;
            $height = $pay_y + 3.8;
            $this->putPricePdf($pdf, $height, $sum_x, $sum, $price_margin);

            $pdf->SetFont($font, null, 8, true);
            // Note
            $note = h($data['NotesPayable']['note']);
            $height   = (mb_strwidth($note, 'utf8') <= 44) ? $note_y + 2.8 : $note_y + 1.2;
            $pdf->SetXY($note_x, $height);
            $pdf->MultiCell(57, 5, $note, 0, 'L');

            if (0 < ($record_count % Configure::read('NOTE_PAYABLE_PDF_ROW'))) {
                $point_y += $point_step;
                $note_y += $note_step;
                $pay_y += $pay_step;
            } else {
                // 計
                $height = $point_start_y + ($point_step * Configure::read('NOTE_PAYABLE_PDF_ROW')) + 1;
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $height, $total_x, $total, $price_margin);
                $total = 0;

                // ページ追加
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $point_y = $point_start_y;
                $note_y = $point_start_y;
                $pay_y = $point_start_y;
            }
        }

        if (0 < ($record_count % Configure::read('NOTE_PAYABLE_PDF_ROW'))) {
            // 計
            $height = $point_start_y + ($point_step * Configure::read('NOTE_PAYABLE_PDF_ROW')) + 1;
            $pdf->SetFont($font, null, 9, true);
            $this->putPricePdf($pdf, $height, $total_x, $total, $price_margin);
        }

        return $pdf;
    }

    /**
     * 買掛金（未払金・未払費用）の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_accounts_payables($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'accounts_payable.pdf');

        $AccountsPayable = ClassRegistry::init('AccountsPayable');
        $UnpaidBonus = ClassRegistry::init('UnpaidBonus');
        $DividendPayable = ClassRegistry::init('DividendPayable');

        $accountPayables  =  $AccountsPayable->findPdfExportData();
        $unpaidBonuses    = $UnpaidBonus->findUnpaidDivedendBonuses();
        $dividendPayables = $DividendPayable->findUnpaidDivedendBonuses();

        $point_start_y = 36.5;      // 出力開始位置起点(縦)
        $point_step = 7.7;          // 次の出力
        $point_y = $height = $point_start_y;  // 出力開始位置(縦)
        $title_x = 28;            // 科目名の表示位置
        $name_x = 45;             // 名称の表示位置
        $address_x = 83;          // 所在地の表示位置
        $balance_x = 135;         // 期末現在高の表示位置
        $note_x = 170;            // 摘要の表示位置
        $subtotal_y = $point_start_y + ($point_step * Configure::read('ACCOUNT_PAYABLE_PDF_ROW')) + 2;

        $record_count = 0;
        $balance_sum = 0;
        $current_page = 1;
        $end_page = ceil(count($accountPayables) / Configure::read('ACCOUNT_PAYABLE_PDF_ROW'));

        $balance_margin = array(0, 1.7, 1.7);

        foreach ($accountPayables as $data) {

            $record_count++;
            $pdf->SetFont($font, null, 7, true);

            // 科目
            $account_title = h($data['AccountTitle']['account_title']);
            $height = (mb_strwidth($account_title, 'utf8') <= 12) ? $point_y + 2 : $point_y;
            $pdf->SetXY($title_x, $height);
            $pdf->MultiCell(18, 5, $account_title, 0, 'C');

            // 名称
            $name = h($data['NameList']['name']);
            $name = (mb_strlen($name, 'utf-8') <= 28) ? $name : mb_substr($name, 0, 27, 'utf-8').'...';
            $height = (mb_strwidth($name, 'utf8') <= 29) ? $point_y + 2 : $point_y;
            $pdf->SetXY($name_x, $height);
            $pdf->MultiCell(38, 5, $name, 0, 'L');

            // 所在地
            $address = h($data['NameList']['prefecture'] . $data['NameList']['city'] . $data['NameList']['address']);
            $pdf->SetFont($font, null, 6, true);
            $height = (mb_strwidth($address, 'utf8') <= 46) ? $point_y + 2 : $point_y;
            $pdf->SetXY($address_x, $height);
            $pdf->MultiCell(51, 5, $address, 0, 'L');

            // 期末現在高
            $balance = $data['AccountsPayable']['balance'];
            $balance_sum += $balance;
            $pdf->SetFont($font, null, 9, true);
            $height = $point_y + 2;
            $this->putPricePdf($pdf, $height, $balance_x, $balance, $balance_margin);

            // 摘要
            $note = h($data['AccountsPayable']['note']);
            $pdf->SetFont($font, null, 7, true);
            $height = (mb_strwidth($note, 'utf8') <= 17) ? $point_y + 2 : $point_y;
            $pdf->SetXY($note_x, $height);
            $pdf->MultiCell(23, 5, $note, 0, 'L');

            if (0 < ($record_count % Configure::read('ACCOUNT_PAYABLE_PDF_ROW'))) {
                $point_y += $point_step;
            } else {
                // 計
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $subtotal_y, $balance_x, $balance_sum, $balance_margin);
                $balance_sum = 0;

                if ($current_page === 1) {
                    // １ページ目なら未払配当金・未払役員賞与出力
                    $pdf->SetFont($font, null, 9, true);
                    $this->_putUnpaidDividendBonusPdf($pdf, $dividendPayables, $unpaidBonuses);
                }

                if ($current_page < $end_page) {
                    // ページ追加
                    $pdf->AddPage();
                    $pdf->useTemplate($template, null, null, null, null, true);
                    $point_y = $point_start_y;
                    $current_page++;
                }
            }
        }

        if (0 < ($record_count % Configure::read('ACCOUNT_PAYABLE_PDF_ROW'))) {
            // 計
            $pdf->SetFont($font, null, 9, true);
            $this->putPricePdf($pdf, $subtotal_y, $balance_x, $balance_sum, $balance_margin);

            if ($current_page === 1) {
                // １ページ目なら未払配当金・未払役員賞与出力
                $pdf->SetFont($font, null, 9, true);
                $this->_putUnpaidDividendBonusPdf($pdf, $dividendPayables, $unpaidBonuses);
            }
        }

        return $pdf;

    }

    /**
     * 未払配当金・未払役員賞与出力
     * @param FPDI OBJ $pdf
     * @param array $dividendPayables  // 未払配当金
     * @param array $unpaidBonuses     // 未払役員賞与
     */
    function _putUnpaidDividendBonusPdf(&$pdf, $dividendPayables, $unpaidBonuses) {

        // 指定数で切り取り
        $dividendPayables = array_slice($dividendPayables, 0, Configure::read('DIVIDEND_PAYABLE_PDF_ROW'));
        $unpaidBonuses = array_slice($unpaidBonuses, 0, Configure::read('UNPAID_BONUS_PDF_ROW'));

        $point_start_y = 264;                  // 出力開始位置起点(縦)
        $point_step = 7.9;                     // 次の出力
        $height = $point_start_y;              // 出力開始位置(縦)
        $dividend_date_x = 55;                 // 未払配当金の日付表示位置
        $dividend_balance_x = 79;              // 未払配当金の期末現在高表示位置
        $unpaid_bonus_date_x = 125;            // 未払役員賞与の日付表示位置
        $unpaid_bonus_balance_x = 149;         // 未払役員賞与の期末現在高表示位置

        $balance_margin = array(0, 1.5, 3);

        // 未払配当金表示
        foreach ($dividendPayables as $data) {
            $this->putHeiseiDate($pdf, $height, $dividend_date_x, $data['DividendPayable']['deal_date']);
            $this->putPricePdf($pdf, $height, $dividend_balance_x, $data['DividendPayable']['balance'], $balance_margin);
            $height += $point_step;
        }

        // 未払役員賞与表示
        $height = $point_start_y;
        foreach ($unpaidBonuses as $data) {
            $this->putHeiseiDate($pdf, $height, $unpaid_bonus_date_x, $data['UnpaidBonus']['deal_date']);
            $this->putPricePdf($pdf, $height, $unpaid_bonus_balance_x, $data['UnpaidBonus']['balance'], $balance_margin);
            $height += $point_step;
        }

    }

    /**
     * Put each number to cell
     * @param FPDI OBJ $pdf
     * @param array $x
     * @param array $y
     * @param array $data
     * @param array $distance
     */
    function _putsCharLeftToRigth(&$pdf, $x, $y, $data, $distance, $align='R') {
        $step = 0;
        for ($i = strlen($data)-1; $i >= 0; $i--) {
            $element = mb_substr($data, $i, 1,'utf-8');
            $pdf->SetXY($x-$step, $y);
            $pdf->MultiCell(10, 5, $element, 0, $align);
            $step += $distance;
        }
    }

    /**
     * 借入金及び支払利子の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_debts($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'debts.pdf');

        $Debt = ClassRegistry::init('Debt');
        $debts  =  $Debt->findPdfExportData();

        $point_start_y = 36.7;     // 出力開始位置起点(縦)
        $point_step = 12.7;        // 次の出力
        $point_y = $height = $point_start_y;  // 出力開始位置(縦)
        $name_x = 27.5;            // 借入先名称の表示位置
        $relationship_x = 58.2;    // 法人・代表者との関係表示位置
        $address_x = 27.5;         // 所在地の表示位置
        $balance_x = 78.9;         // 期末現在高の表示位置
        $interest_expense_x = 110; // 期中支払利息額の表示位置
        $rate_x = 110;             // 利率の表示位置
        $reason_x = 134;           // 借入理由の表示位置
        $collateral_x = 158;       // 担保の内容の表示位置

        $balance_margin = array(0, 0.1, 0.5);

        $record_count = 0;
        $balance_sum = 0;
        $interest_expense_sum = 0;
        $current_page = 1;
        $end_page = ceil(count($debts) / Configure::read('DEBT_PDF_ROW'));

        foreach ($debts as $data) {

            $record_count++;
            $pdf->SetFont($font, null, 7, true);

            // 借入先名称
            $name = h($data['NameList']['name']);
            if ($name) {
                $name = $this->roundLineStrByWidth($name, 24);
                $height = (mb_strwidth($name, 'utf8') <= 24) ? $point_y + 2 : $point_y;
                $pdf->SetXY($name_x, $height);
                $pdf->MultiCell(33, 5, $name, 0, 'L');
            }

            // 法人・代表者との関係
            $relationship = h($data['NameList']['relationship']);
            if ($relationship) {
                $relationship = $this->roundLineStrByWidth($relationship, 16);
                $height = (mb_strwidth($relationship, 'utf8') <= 16) ? $point_y + 2 : $point_y;
                $pdf->SetXY($relationship_x, $height);
                $pdf->MultiCell(22, 5, $relationship, 0, 'C');
            }

            // 所在地
            $pdf->SetFont($font, null, 6, true);
            $address = h($data['NameList']['prefecture'] . $data['NameList']['city'] . $data['NameList']['address']);
            if ($address) {
                $address = $this->roundLineStrByWidth($address, 48);
                $height = (mb_strwidth($address, 'utf8') <= 48) ? $point_y + 1 : $point_y;
                $pdf->SetXY($address_x, $height + 7);
                $pdf->MultiCell(54, 5, $address, 0, 'L');
            }

            // 期末現在高
            $balance = $data['Debt']['balance'];
            $balance_sum += $balance;
            $pdf->SetFont($font, null, 9, true);
            $height = $point_y + 5;
            $this->putPricePdf($pdf, $height, $balance_x, $balance, $balance_margin);

            // 期中支払利息額
            $interest_expense = $data['Debt']['interest_expense'];
            if ($interest_expense) {
                $interest_expense_sum += $interest_expense;
                $pdf->SetFont($font, null, 9, true);
                $height = $point_y + 2;
                $pdf->SetXY($interest_expense_x, $height);
                $pdf->MultiCell(23, 5, number_format($interest_expense), 0, 'R');
            }

            // 利率
            $rate = $data['Debt']['rate'];
            if ($rate) {
                $pdf->SetFont($font, null, 9, true);
                $height = $point_y + 8;
                $pdf->SetXY($rate_x, $height);
                $pdf->MultiCell(23, 5, number_format($rate, 2).'%', 0, 'R');
            }

            $pdf->SetFont($font, null, 7, true);

            // 借入理由
            $reason = h($data['Debt']['reason']);
            if ($reason) {
                $reason = $this->roundLineStrByWidth($reason, 18, 3);
                $row = ceil(mb_strwidth(str_replace("\n",'', $reason), 'utf8') / 18);  // 行数
                $height = $point_y + (7 - ($row * 1.8));
                $pdf->SetXY($reason_x, $height);
                $pdf->MultiCell(25, 10, $reason, 0, 'L');
            }

            // 担保の内容
            $collateral = h($data['Debt']['collateral']);
            if ($collateral) {
                $collateral = $this->roundLineStrByWidth($collateral, 26, 3);
                $row = ceil(mb_strwidth(str_replace("\n",'', $collateral), 'utf8') / 26);  // 行数
                $height = $point_y + (7 - ($row * 1.8));
                $pdf->SetXY($collateral_x, $height);
                $pdf->MultiCell(35, 10, $collateral, 0, 'L');
            }

            if (0 < ($record_count % Configure::read('DEBT_PDF_ROW'))) {
                $point_y += $point_step;
            } else {
                // 計
                $height = $point_start_y + ($point_step * Configure::read('DEBT_PDF_ROW')) + 5;
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $height, $balance_x, $balance_sum, $balance_margin);

                // 利息計
                $height = $point_start_y + ($point_step * Configure::read('DEBT_PDF_ROW')) + 2;
                $pdf->SetFont($font, null, 9, true);
                $pdf->SetXY($interest_expense_x, $height);
                $pdf->MultiCell(23, 5, number_format($interest_expense_sum), 0, 'R');

                $balance_sum = $interest_expense_sum = 0;

                if ($current_page < $end_page) {
                    // ページ追加
                    $pdf->AddPage();
                    $pdf->useTemplate($template, null, null, null, null, true);
                    $point_y = $point_start_y;
                    $current_page++;
                }
            }
        }

        if (0 < ($record_count % Configure::read('DEBT_PDF_ROW'))) {
                // 計
                $height = $point_start_y + ($point_step * Configure::read('DEBT_PDF_ROW')) + 5;
                $pdf->SetFont($font, null, 9, true);
                $this->putPricePdf($pdf, $height, $balance_x, $balance_sum, $balance_margin);

                // 利息計
                $height = $point_start_y + ($point_step * Configure::read('DEBT_PDF_ROW')) + 2;
                $pdf->SetFont($font, null, 9, true);
                $pdf->SetXY($interest_expense_x, $height);
                $pdf->MultiCell(23, 5, number_format($interest_expense_sum), 0, 'R');
        }

        return $pdf;

    }

    /**
     * 仮受金（前受金・預り金）の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_suspense_receipts($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'suspense_receipts.pdf');

        $SuspenseReceipt = ClassRegistry::init('SuspenseReceipt');
        $DepositsPayable = ClassRegistry::init('DepositsPayable');

  	    $suspenseReceipts =  $SuspenseReceipt->findPdfExportData();
  	    $depositsPayables =  $DepositsPayable->findPdfExportData();

        // 1ページ出力分ごとに分割
        $chunkSuspenseReceipts = array_chunk($suspenseReceipts, Configure::read('SUSPENSE_RECEIPT_PDF_ROW'));
        $chunkDepositsPayables = array_chunk($depositsPayables, Configure::read('DEPOSIT_PAYABLE_PDF_ROW') * 2);

        $end_page = max(count($chunkSuspenseReceipts), count($chunkDepositsPayables));

        for ($current_page = 1; $current_page <= $end_page; $current_page++) {

            // 仮受金出力
            if (isset($chunkSuspenseReceipts[$current_page - 1])) {
                $this->_putSuspenseReceiptPDF($pdf, $font, $chunkSuspenseReceipts[$current_page - 1]);
            }
            // 預り金出力
            if (isset($chunkDepositsPayables[$current_page - 1])) {
                $this->_putDepositsPayablePDF($pdf, $font, $chunkDepositsPayables[$current_page - 1]);
            }

            if ($current_page < $end_page) {
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
            }

        }

        return $pdf;

    }

    /**
     * 仮受金出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS OBJ $font
     * @param array $suspenseReceipts
     */
    function _putSuspenseReceiptPDF(&$pdf, $font, $suspenseReceipts) {

        $point_start_y = 37;      // 出力開始位置起点(縦)
        $point_step = 9.1;          // 次の出力
        $point_y = $height = 37;  // 出力開始位置(縦)
        $title_x = 30;              // 科目名の表示位置
        $name_x = 45.5;               // 名称の表示位置
        $address_x = 74.5;            // 所在地の表示位置
        $relationship_x = 119;      // 関係の表示位置
        $balance_x = 134;           // 期末現在高の表示位置
        $note_x = 170;              // 取引の内容の表示位置

        $line_margin = 3;
        $line_margin_half = $line_margin / 2;
        $balance_margin = array(0, 1.3, 2.7);

        foreach ($suspenseReceipts as $key => $data) {

            // 科目
            $pdf->SetFont($font, null, 7, true);
            $account_title = h($data['AccountTitle']['account_title']);
            $account_title = $this->roundLineStrByWidth($account_title, 8, 2);
            $height = (mb_strwidth($account_title, 'utf8') <= 8) ? $point_y + $line_margin : $point_y + $line_margin_half;
            $pdf->SetXY($title_x, $height);
            $pdf->MultiCell(20, 5, $account_title, 0, 'L');

            // 名称
            $name = h($data['NameList']['name']);
            $name = $this->roundLineStrByWidth($name, 20);
            $height = (mb_strwidth($name, 'utf8') <= 20) ? $point_y + $line_margin : $point_y + $line_margin_half;
            $pdf->SetXY($name_x, $height);
            $pdf->MultiCell(29, 5, $name, 0, 'L');

            // 所在地
            $address = h($data['NameList']['prefecture'] . $data['NameList']['city'] . $data['NameList']['address']);
            $address = $this->roundLineStrByWidth($address, 32);
            $height = (mb_strwidth($address, 'utf8') <= 32) ? $point_y + $line_margin : $point_y + $line_margin_half;
            $pdf->SetXY($address_x, $height);
            $pdf->MultiCell(42, 5, $address, 0, 'L');

            // 関係
            $relationship = h($data['NameList']['relationship']);
            $relationship = $this->roundLineStrByWidth($relationship, 8);
            $height = (mb_strwidth($relationship, 'utf8') <= 8) ? $point_y + $line_margin : $point_y + $line_margin_half;
            $pdf->SetXY($relationship_x, $height);
            $pdf->MultiCell(13, 5, $relationship, 0, 'L');

            // 期末現在高
            $pdf->SetFont($font, null, 9, true);
            $balance = $data['SuspenseReceipt']['balance'];
            $height = $point_y + $line_margin;
            $this->putPricePdf($pdf, $height, $balance_x, $balance, $balance_margin);

            // 取引の内容
            $pdf->SetFont($font, null, 7, true);
            $note = h($data['SuspenseReceipt']['note']);
            $note = $relationship = $this->roundLineStrByWidth($note, 16);
            $height = (mb_strwidth($note, 'utf8') <= 16) ? $point_y + $line_margin : $point_y + $line_margin_half;
            $pdf->SetXY($note_x, $height);
            $pdf->MultiCell(23, 5, $note, 0, 'L');

            $point_y += $point_step;
        }

    }

    /**
     * 貸付金出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS OBJ $font
     * @param array $depositsPayables
     */
    function _putDepositsPayablePDF(&$pdf, $font, $depositsPayables) {

        $point_start_y = 214.5;     // 出力開始位置起点(縦)
        $point_step = 8.9;        // 次の出力
        $point_y = $height = 214.5; // 出力開始位置(縦)
        $pay_date_x = 29;           // 支払年月の表示位置
        $income_class_x = 58;       // 所得の種類表示位置
        $balance_x = 71.5;          // 期末現在高の表示位置

        $right_margin_x = 83;
        $balance_margin = array(0, 2.3, 4.5);
        $line_count = 0;
        $put_right = false;


        foreach ($depositsPayables as $key => $data) {
            $line_count++;
            // 表右側へ出力
            if (Configure::read('DEPOSIT_PAYABLE_PDF_ROW') + 1 === $line_count) {
                $put_right = true;
                $point_y = $point_start_y;
            }

            // 名称
            $pdf->SetFont($font, null, 9, true);
            $pay_date = $data['DepositsPayable']['pay_date'];
            if ($pay_date) {
                $x = ($put_right) ? $pay_date_x + $right_margin_x : $pay_date_x;
                $this->putHeiseiDate($pdf, $point_y, $x, $pay_date, array(0, 4), false);
            }

            // 所得の種類
            $pdf->SetFont($font, null, 8, true);
            $income_class = h($data['DepositsPayable']['income_class']);
            if ($income_class) {
                $x = ($put_right) ? $income_class_x + $right_margin_x : $income_class_x;
                $pdf->SetXY($x, $point_y);
                $pdf->MultiCell(5, 5, $income_class, 0, 'C');
            }

            // 期末現在高
            $pdf->SetFont($font, null, 9, true);
            $balance = $data['DepositsPayable']['balance'];
            $x = ($put_right) ? $balance_x + $right_margin_x - 0.4 : $balance_x;
            $this->putPricePdf($pdf, $point_y, $x, $balance, $balance_margin);

            $point_y += $point_step;

        }
    }

    /**
     * 売掛金（未収入金）の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_non_operatings($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'non_operatings.pdf');

        $modelCredit = ClassRegistry::init('CreditNonOperating');
        $modelDebit  = ClassRegistry::init('DebitNonOperating');

				$mainData = $modelCredit->findPdfExportData();
				$subData  = $modelDebit->findPdfExportData();
				$max_count = (count($mainData) >= count($subData)) ? count($mainData) : count($subData);

        $mdModelName = array_keys($mainData[0])[0];
        $smModelName = array_keys($subData[0])[0];

        $point_start_y     = 41;
        $point_y           = 41;
        $point_sub_start_y = 152.5;
        $point_sub_y       = 152.5;
        $point_step        = 11.1;
        $height            = 41;

        $account_title_x = 40;
        $trans_content_x = 59.5;
        $name_list_x     = 90.5;
        $address_x       = 123;
        $sum_x           = 152.5;

        $record_count = 0;
        $each_block_count = 0;

        //foreach ($mainData as $key => $md) {
        for ($i = 0; $i < $max_count; $i++) {
            $record_count++;
            $pdf->SetFont($font, null, 7, true);

			if (isset($mainData[$i])) {
			  // account title
			  $mdAccountTitle = h($mainData[$i]['AccountTitle']['account_title']);
			  $height = (mb_strwidth($mdAccountTitle, 'utf8') <= 12) ? $point_y : $point_y - 3.5;
			  $pdf->SetXY($account_title_x, $height);
			  $pdf->MultiCell(18, 5, $mdAccountTitle, 0, 'L');
			  // transaction content
			  $mdTransContent = $this->roundLineStrByWidth($mainData[$i][$mdModelName]['transaction_content'], 22);
			  $height = (mb_strwidth($mdTransContent, 'utf8') <= 22) ? $point_y : $point_y - 3.1;
			  $pdf->SetXY($trans_content_x, $height);
			  $pdf->MultiCell(30, 5, $mdTransContent, 0, 'L');
			  // name list
			  $mdNameList = $this->roundLineStrByWidth($mainData[$i]['NameList']['name'], 24, 3);
			  $height = (mb_strwidth($mdNameList, 'utf8') <= 24) ? $point_y : $point_y - 3.1;
			  if (mb_strwidth($mdNameList, 'utf8') > 49) {
				$height = $point_y - 5.8;
			  }
			  $pdf->SetXY($name_list_x, $height);
			  $pdf->MultiCell(35, 5, $mdNameList, 0, 'L');
			  // address
			  $_mdAddress = h($mainData[$i]['NameList']['prefecture']) . h($mainData[$i]['NameList']['city']) . h($mainData[$i]['NameList']['address']);
			  $mdAddress = $this->roundLineStrByWidth($_mdAddress, 30, 3);
			  $height = (mb_strwidth($mdAddress, 'utf8') <= 30) ? $point_y : $point_y - 3.1;
			  if (mb_strwidth($mdAddress, 'utf8') > 61) {
				$height = $point_y - 5.8;
			  }
			  $pdf->SetXY($address_x, $height);
			  $pdf->MultiCell(40, 5, $mdAddress, 0, 'L');
			  // sum
        $pdf->SetFont($font, null, 9, true);
			  $mdSum = $mainData[$i][$mdModelName]['sum'];
			  $height = $point_y + 0.3;
			  $m1 = substr($mdSum, 0, -6); //百万
			  $m2 = substr($mdSum, -6, -3);//千
			  $m3 = substr($mdSum, -3);    //円
			  $pdf->SetXY($sum_x, $height);
			  $pdf->MultiCell(20, 5, $m1, 0, 'R');
			  $pdf->SetXY($sum_x+8.2, $height);
			  $pdf->MultiCell(20, 5, $m2, 0, 'R');
			  $pdf->SetXY($sum_x+30.6, $height);
			  $pdf->MultiCell(25, 5, $m3, 0, 'L');
			}

            if (isset($subData[$i])) {
        $pdf->SetFont($font, null, 7, true);
			  // account title
			  $smAccountTitle = h($subData[$i]['AccountTitle']['account_title']);
			  $height = (mb_strwidth($smAccountTitle, 'utf8') <= 12) ? $point_sub_y : $point_sub_y - 3.5;
			  $pdf->SetXY($account_title_x, $height);
			  $pdf->MultiCell(18, 5, $smAccountTitle, 0, 'L');
			  // transaction content
			  $smATransContent = $this->roundLineStrByWidth($subData[$i][$smModelName]['transaction_content'], 22);
			  $height = (mb_strwidth($smATransContent, 'utf8') <= 22) ? $point_sub_y : $point_sub_y - 3.1;
			  $pdf->SetXY($trans_content_x, $height);
			  $pdf->MultiCell(30, 5, $smATransContent, 0, 'L');
			  // name list
			  $smNameList = $this->roundLineStrByWidth($subData[$i]['NameList']['name'], 24, 3);
			  $height = (mb_strwidth($smNameList, 'utf8') <= 24) ? $point_sub_y : $point_sub_y - 3.1;
			  if (mb_strwidth($mdNameList, 'utf8') > 49) {
				$height = $point_y - 5.8;
			  }
			  $pdf->SetXY($name_list_x, $height);
			  $pdf->MultiCell(35, 5, $smNameList, 0, 'L');
			  // address
			  $_smAddress = h($subData[$i]['NameList']['prefecture']) . h($subData[$i]['NameList']['city']) . h($subData[$i]['NameList']['address']);
			  $smAddress = $this->roundLineStrByWidth($_smAddress, 30, 3);
			  $height = (mb_strwidth($smAddress, 'utf8') <= 30) ? $point_sub_y : $point_sub_y - 3.1;
			  if (mb_strwidth($mdAddress, 'utf8') > 61) {
				$height = $point_y - 5.8;
			  }
			  $pdf->SetXY($address_x, $height);
			  $pdf->MultiCell(40, 5, $smAddress, 0, 'L');
			  // sum
        $pdf->SetFont($font, null, 9, true);
			  $smSum = $subData[$i][$smModelName]['sum'];
			  $height = $point_sub_y + 0.3;
			  $m1 = substr($smSum, 0, -6); //百万
			  $m2 = substr($smSum, -6, -3);//千
			  $m3 = substr($smSum, -3);    //円
			  $pdf->SetXY($sum_x, $height);
			  $pdf->MultiCell(20, 5, $m1, 0, 'R');
			  $pdf->SetXY($sum_x+8.2, $height);
			  $pdf->MultiCell(20, 5, $m2, 0, 'R');
			  $pdf->SetXY($sum_x+30.6, $height);
			  $pdf->MultiCell(25, 5, $m3, 0, 'L');
            }


            if (0 < ($record_count % Configure::read('NON_OPERATING_PDF_ROW'))) {
                $point_y += $point_step;
                $point_sub_y += $point_step;
            } else {
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $point_y = $point_start_y;
                $point_sub_y = $point_sub_start_y;
            }
        }

        return $pdf;
    }

    function export_rents($pdf, $font) {
        $template = $this->setTemplateAddPage($pdf, $font, 'rents.pdf');

        $Rent      = ClassRegistry::init('Rent');
        $Forgift   = ClassRegistry::init('Forgift');
        $License   = ClassRegistry::init('License');
        $rents     =  $Rent->findPdfExportData();
        $forgifts  =  $Forgift->findPdfExportData();
        $licenses  =  $License->findPdfExportData();

        // 1ページ出力分ごとに分割
        $chunkRents    = array_chunk($rents, Configure::read('RENT_PDF_ROW'));
        $chunkForgifts = array_chunk($forgifts, Configure::read('FORGIFT_PDF_ROW'));
        $chunkLicenses = array_chunk($licenses, Configure::read('LICENSE_PDF_ROW'));

        $end_page = max(count($chunkRents), count($chunkForgifts), count($chunkLicenses));

        for ($current_page = 1; $current_page <= $end_page; $current_page++) {

            // 地代家賃の支払対象期間のテンプレート補正
            $point_y = 46;
            $point_step = 10.95;
            $pdf->SetFont($font, null, 8, true);
            for ($line = 0; $line < Configure::read('RENT_PDF_ROW'); $line++) {
                $pdf->Rect(133.5, $point_y, 25.5, 3, 'F', array(), array(255,255,255));
                $pdf->SetXY(133.5, $point_y);
                $pdf->MultiCell(25.5, 5,  '  ・   〜   ・   ', 0, 'C');
                $point_y += $point_step;
            }

            // 地代家賃出力
            if (isset($chunkRents[$current_page - 1])) {
                $this->_putRentPDF($pdf, $font, $chunkRents[$current_page - 1]);
            }
            // 権利金出力
            if (isset($chunkForgifts[$current_page - 1])) {
                $this->_putForgiftPDF($pdf, $font, $chunkForgifts[$current_page - 1]);
            }
            // 工業所有権等出力
            if (isset($chunkLicenses[$current_page - 1])) {
                $this->_putLicensePDF($pdf, $font, $chunkLicenses[$current_page - 1]);
            }

            if ($current_page < $end_page) {
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
            }

        }

        return $pdf;

    }

    /**
     * 地代家賃出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $rents  // 地代家賃
     */
    function _putRentPDF(&$pdf, $font, $rents) {

        $point_step    = 10.95;         // 次の出力
        $point_y       = $height = 45;  // 出力開始位置(縦)
        $rent_class_x  = 27;            // 地代家賃区分の表示位置
        $purpose_x     = 48;            // 物件の用途表示位置
        $location_x     = 48;           // 物件の所在地の表示位置
        $name_x        = 91;            // 貸主の名称の表示位置
        $address_x     = 91;            // 貸主住所の表示位置
        $start_date_x  = 133.5;         // 支払対象期間(自)
        $end_date_x    = 147.5;         // 支払対象期間(至)
        $sum_x         = 133.5;         // 支払賃借料の表示位置
        $note_x        = 160;           // 摘要の表示位置

        $line_margin = 3.8;
        $line_top_margin = 1;
        $line_bottom_margin = 6.5;

        foreach ($rents as $data) {

            $pdf->SetFont($font, null, 7, true);

            // 地代家賃区分
            $rent_class = $data['Rent']['rent_class'];
            if ($rent_class) {
                $height = $point_y + $line_margin;
                $pdf->SetXY($rent_class_x, $height);
                $pdf->MultiCell(22, 5, $rent_class, 0, 'C');
            }

            // 物件の用途
            $purpose = h($data['Rent']['purpose']);
            if ($purpose) {
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($purpose_x, $height);
                $pdf->MultiCell(42, 5, $purpose, 0, 'L');
            }

            // 物件の所在地
            $location = h($data['Rent']['prefecture']. $data['Rent']['city']. $data['Rent']['address']);
            if ($location) {
                if (32 < mb_strwidth($location, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $height = (mb_strwidth($location, 'utf8') <= 37) ? $point_y + $line_bottom_margin : $point_y + 5.4;
                $location = $this->roundLineStrByWidth($location, 37);
                $pdf->SetXY($location_x, $height);
                $pdf->MultiCell(42, 5,  $location, 0, 'L');
            }

            // 貸主の名称
            $pdf->SetFont($font, null, 7, true);
            $name = h($data['NameList']['name']);
            if ($name) {
                $name = $this->roundLineStrByWidth($name, 32, 1);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($name_x, $height);
                $pdf->MultiCell(42, 5, $name, 0, 'L');
            }

            // 貸主住所
            $address = h($data['NameList']['prefecture']. $data['NameList']['city']. $data['NameList']['address']);
            if ($address) {
                if (32 < mb_strwidth($address, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $height = (mb_strwidth($address, 'utf8') <= 37) ? $point_y + $line_bottom_margin : $point_y + 5.4;
                $address = $this->roundLineStrByWidth($address, 37);
                $pdf->SetXY($address_x, $height);
                $pdf->MultiCell(42, 5,  $address, 0, 'L');
            }

            // 支払対象期間
            $pdf->SetFont($font, null, 8, true);
            $start_date = $data['Rent']['start_date'];
            if ($start_date) {
                $height = $point_y + $line_top_margin;
                $this->putHeiseiDate($pdf, $height, $start_date_x, $start_date, array(-1, -3.8), false);
            }
            $end_date = $data['Rent']['end_date'];
            if ($end_date) {
                $height = $point_y + $line_top_margin;
                $this->putHeiseiDate($pdf, $height, $end_date_x, $end_date, array(-1, -3.8), false);
            }

            // 支払賃借料
            $sum = $data['Rent']['sum'];

            if ($sum != "") {
                $sum = number_format($sum);
                $height = $point_y + $line_bottom_margin;
                $pdf->SetXY($sum_x, $height);
                $pdf->MultiCell(23, 5,  $sum, 0, 'R');
            }

            // 摘要
            $note = h($data['Rent']['note']);
            if ($note) {
              $pdf->SetFont($font, null, 7, true);
                $note = $this->roundLineStrByWidth($note, 24, 2);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($note_x, $height);
                $pdf->MultiCell(34, 5, $note, 0, 'L');
            }

            $point_y += $point_step;
        }

    }

    /**
     * 権利金出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $forgifts  // 権利金
     */
    function _putForgiftPDF(&$pdf, $font, $forgifts) {

        $point_step    = 10.95;          // 次の出力
        $point_y       = $height = 155;  // 出力開始位置(縦)
        $name_x        = 28;             // 支払先の名称の表示位置
        $address_x     = 28;             // 支払先の住所の表示位置
        $deal_date_x   = 76.5;           // 支払年月日の表示位置
        $sum_x         = 101;            // 支払額の表示位置
        $detail_x      = 133;            // 権利金等の内容の表示位置
        $note_x        = 159.5;          // 摘要の表示位置

        $line_margin = 3.9;
        $line_top_margin = 1;
        $line_bottom_margin = 7;

        foreach ($forgifts as $data) {

            $pdf->SetFont($font, null, 7, true);

            // 支払先の名称
            $name = $data['NameList']['name'];
            if ($name) {
                $name = $this->roundLineStrByWidth($name, 34, 1);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($name_x, $height);
                $pdf->MultiCell(46, 5, $name, 0, 'C');
            }

            // 支払先の住所
            $address = h($data['NameList']['prefecture']. $data['NameList']['city']. $data['NameList']['address']);
            if ($address) {
                if (37 < mb_strwidth($address, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $height = (mb_strwidth($address, 'utf8') <= 41) ? $point_y + $line_bottom_margin : $point_y + 5.6;
                $address = $this->roundLineStrByWidth($address, 41);
                $pdf->SetXY($address_x, $height);
                $pdf->MultiCell(46, 5, $address, 0, 'L');
            }

            // 支払年月日
            $pdf->SetFont($font, null, 8, true);
            $deal_date = $data['Forgift']['deal_date'];
            if ($deal_date) {
                $height = $point_y + $line_margin;
                $this->putHeiseiDate($pdf, $height, $deal_date_x, $deal_date);
            }

            // 支払金額
            $pdf->SetFont($font, null, 8, true);
            $sum = $data['Forgift']['sum'];
            if ($sum != "") {
                $height = $point_y + $line_margin;
                $this->putPricePdf($pdf, $height, $sum_x, $sum, array(0, 0.5, 1.3));
            }

            // 権利金の内容
            $pdf->SetFont($font, null, 7, true);
            $detail = h($data['Forgift']['detail']);
            if ($detail) {
                $detail = $this->roundLineStrByWidth($detail, 20);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($detail_x, $height);
                $pdf->MultiCell(28, 5, $detail, 0, 'L');
            }

            // 摘要
            $note = h($data['Forgift']['note']);
            if ($note) {
                $note = $this->roundLineStrByWidth($note, 25);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($note_x, $height);
                $pdf->MultiCell(34, 5, $note, 0, 'L');
            }

            $point_y += $point_step;

        }
    }

    /**
     * 工業所有権等の使用料出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $licenses  // 工業所有権等の使用料
     */
    function _putLicensePDF(&$pdf, $font, $forgifts) {

        $point_step    = 10.95;          // 次の出力
        $point_y       = $height = 227.5;  // 出力開始位置(縦)
        $account_title_x = 28;           // 名称の表示位置
        $name_x        = 45;             // 支払先の名称の表示位置
        $address_x     = 45;             // 支払先の住所の表示位置
        $contract_start_date_x = 91;     // 契約期間(自)
        $contract_end_date_x   = 102.5;  // 契約期間(至)
        $pay_start_date_x      = 110.8;  // 支払対象期間(自)
        $pay_end_date_x        = 122.3;  // 支払対象期間(至)
        $sum_x         = 130;            // 支払額の表示位置
        $note_x        = 159.5;          // 摘要の表示位置

        $line_margin = 3.8;
        $line_top_margin = 1;
        $line_bottom_margin = 6.5;
        $date_margin = array(0, -4);

        foreach ($forgifts as $data) {

            $pdf->SetFont($font, null, 7, true);

            // 名称
            $account_title = $data['AccountTitle']['account_title'];
            if ($account_title) {
                $height = $point_y + $line_margin;
                $pdf->SetXY($account_title_x, $height);
                $pdf->MultiCell(17, 5, $account_title, 0, 'C');
            }

            // 支払先の名称
            $name = $data['NameList']['name'];
            if ($name) {
                $name = $this->roundLineStrByWidth($name, 36, 1);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($name_x, $height);
                $pdf->MultiCell(47, 5, $name, 0, 'C');
            }

            // 支払先の住所
            $address = h($data['NameList']['prefecture']. $data['NameList']['city']. $data['NameList']['address']);
            if ($address) {
                if (37 < mb_strwidth($address, 'utf8')) {
                    $pdf->SetFont($font, null, 6, true);
                }
                $height = (mb_strwidth($address, 'utf8') <= 43) ? $point_y + $line_bottom_margin : $point_y + 5.6;
                $address = $this->roundLineStrByWidth($address, 43);
                $pdf->SetXY($address_x, $height);
                $pdf->MultiCell(48, 5, $address, 0, 'L');
            }

            // 契約期間
            $pdf->SetFont($font, null, 7, true);
            $contract_start_date = $data['License']['contract_start_date'];
            if ($contract_start_date) {
                $height = $point_y + $line_margin;
                $this->putHeiseiDate($pdf, $height, $contract_start_date_x, $contract_start_date, $date_margin, false);
            }
            $contract_end_date = $data['License']['contract_end_date'];
            if ($contract_end_date) {
                $height = $point_y + $line_margin;
                $this->putHeiseiDate($pdf, $height, $contract_end_date_x, $contract_end_date, $date_margin, false);
            }

            // 支払対象期間
            $pay_start_date = $data['License']['pay_start_date'];
            if ($pay_start_date) {
                $height = $point_y + $line_margin;
                $this->putHeiseiDate($pdf, $height, $pay_start_date_x, $pay_start_date, $date_margin, false);
            }
            $pay_end_date = $data['License']['pay_end_date'];
            if ($pay_end_date) {
                $height = $point_y + $line_margin;
                $this->putHeiseiDate($pdf, $height, $pay_end_date_x, $pay_end_date, $date_margin, false);
            }

            // 支払額
            $pdf->SetFont($font, null, 8, true);
            $sum = $data['License']['sum'];
            if ($sum != "") {
                $height = $point_y + $line_margin;
                $this->putPricePdf($pdf, $height, $sum_x, $sum);
            }

            // 摘要
            $pdf->SetFont($font, null, 7, true);
            $note = h($data['License']['note']);
            if ($note) {
                $note = $this->roundLineStrByWidth($note, 25);
                $height = $point_y + $line_top_margin;
                $pdf->SetXY($note_x, $height);
                $pdf->MultiCell(35, 5, $note, 0, 'L');
            }

            $point_y += $point_step;

        }
    }
    /**
    * 欠損金又は災害損失金の損金算入に関する明細書PDF生成
    * @param FPDI OBJ $pdf
    * @param TCPDF_FONTS $font
    * @return FPDI OBJ $pdf
    */
   function export_schedules7s($pdf, $font) {

     $Schedules7 = ClassRegistry::init('Schedules7');

     //事業年度で様式選択
     $term_info = $Schedules7->getCurrentTerm();
     $target_day = '2016/01/01';
     $target_day29 = '2017/04/01';
     if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
       $template = $this->setTemplateAddPage($pdf, $font, 'schedules7_e290401.pdf');
     } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
       $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules7.pdf');
     } else {
       $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules7.pdf');
     }


        $FixedAsset = ClassRegistry::init('FixedAsset');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules168 = ClassRegistry::init('Schedules168');
        $Term = ClassRegistry::init('Term');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $schedules7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);

        $user = CakeSession::read('Auth.User');
        $term_id = CakeSession::read('Auth.User.term_id');

        $pdf->SetFont($font, null, 12, true);

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
        )));

        $y1 = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
    	$m1 = date('n',strtotime($term['Term']['account_beggining'])) ;
    	$d1 = date('j',strtotime($term['Term']['account_beggining'])) ;

    	$pdf->SetFont($font, null, 10, true);
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
      	$pdf->SetXY(85, 13.5);
      	$pdf->MultiCell(38, 5, $y1, 0, 'R');
      	$pdf->SetXY(91, 13.5);
      	$pdf->MultiCell(38, 5, $m1, 0, 'R');
      	$pdf->SetXY(99, 13.5);
      	$pdf->MultiCell(38, 5, $d1, 0, 'R');
      } else {
        $pdf->SetXY(86.5, 21.5);
      	$pdf->MultiCell(38, 5, $y1, 0, 'R');
      	$pdf->SetXY(93.5, 21.5);
      	$pdf->MultiCell(38, 5, $m1, 0, 'R');
      	$pdf->SetXY(101, 21.5);
      	$pdf->MultiCell(38, 5, $d1, 0, 'R');
      }

    	$y2 = date('Y',strtotime($term['Term']['account_end'])) -1988;
    	$m2 = date('n',strtotime($term['Term']['account_end'])) ;
    	$d2 = date('j',strtotime($term['Term']['account_end'])) ;

    	$pdf->SetFont($font, null, 10, true);
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
      	$pdf->SetXY(85, 18.5);
      	$pdf->MultiCell(38, 5, $y2, 0, 'R');
      	$pdf->SetXY(91, 18.5);
      	$pdf->MultiCell(38, 5, $m2, 0, 'R');
      	$pdf->SetXY(99, 18.5);
      	$pdf->MultiCell(39, 5, $d2, 0, 'R');
      } else {
        $pdf->SetXY(86.5, 26.5);
      	$pdf->MultiCell(38, 5, $y2, 0, 'R');
      	$pdf->SetXY(93.5, 26.5);
      	$pdf->MultiCell(38, 5, $m2, 0, 'R');
      	$pdf->SetXY(101, 26.5);
      	$pdf->MultiCell(39, 5, $d2, 0, 'R');
      }

        // 名称
        $pdf->SetFont($font, null, 8, true);
        $user_name = $schedules7['name'];

        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $user_name = substr($user_name, 0, 90);
          $height = (mb_strwidth($user_name, 'utf8') <= 32) ? 16.3 : 15.3;
          if (mb_strwidth($user_name, 'utf8') <= 32) {
              $pdf->SetXY(151.8, $height);
              $pdf->MultiCell(48, 5, $user_name, 0, 'C');
          } else {
              $pdf->SetXY(151.8, $height);
              $pdf->MultiCell(48, 5, $user_name, 0, 'L');
          }
        } else {
          $height = (mb_strwidth($user_name, 'utf8') <= 30) ? 24.3 : 23.3;
          $user_name = substr($user_name, 0, 90);
          if (mb_strwidth($user_name, 'utf8') <= 30) {
              $pdf->SetXY(152, $height);
              $pdf->MultiCell(46, 4, $user_name, 0, 'C');
          } else {
              $pdf->SetXY(152, $height);
              $pdf->MultiCell(46, 4, $user_name, 0, 'L');
          }
        }

        $pdf->SetFont($font, null, 10, true);

        if (!empty($schedules7['pre_shotoku'])) {
            $pre_shotoku = number_format($schedules7['pre_shotoku']);
            $pre_shotoku = str_replace('-', '△', $pre_shotoku);
            $total_limit = number_format($schedules7['total_limit']);
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $height = (mb_strwidth($pre_shotoku, 'utf8') <= 14) ? 30.2 : 26.7;
              $pdf->SetXY(80, $height );
              $pdf->MultiCell(28, 5, $pre_shotoku, 0, 'R');
              $pdf->SetXY(170, $height);
              $pdf->MultiCell(28, 5, $total_limit, 0, 'R');
            } else {
              $height = (mb_strwidth($pre_shotoku, 'utf8') <= 14) ? 37.7 : 34.2;
              $pdf->SetXY(80, $height );
              $pdf->MultiCell(28, 5, $pre_shotoku, 0, 'R');
              $pdf->SetXY(165, $height);
              $pdf->MultiCell(28, 5, $total_limit, 0, 'R');
            }
        }

        $step = 0;
        if (!empty($schedules7['Schedules7'])) {
            for ($i = 9; $i >= 1; $i--) {
              if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                $pdf->SetFont($font, null, 10, true);
              } else {
                $pdf->SetFont($font, null, 9, true);
              }
                $beggining = $schedules7['Schedules7']['term'.$i.'_beggining'];
                $end = $schedules7['Schedules7']['term'.$i.'_end'];
                if (!empty($beggining)) {
                    $term_beggining_y = date('Y',strtotime($schedules7['Schedules7']['term'.$i.'_beggining'])) -1988;
                    $term_beggining_m = date('n',strtotime($schedules7['Schedules7']['term'.$i.'_beggining'])) ;
                    $term_beggining_d = date('j',strtotime($schedules7['Schedules7']['term'.$i.'_beggining'])) ;
                  if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                    $pdf->SetXY(21.5, 59.4+ $step);
                    $pdf->MultiCell(10, 5, $term_beggining_y, 0, 'R');
                    $pdf->SetXY(28,59.4 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_m, 0, 'R');
                    $pdf->SetXY(34, 59.4 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_d, 0, 'R');
                  } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                    $pdf->SetXY(19, 52.5+ $step);
                    $pdf->MultiCell(10, 5, $term_beggining_y, 0, 'R');
                    $pdf->SetXY(26.5, 52.5 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_m, 0, 'R');
                    $pdf->SetXY(32.5, 52.5 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_d, 0, 'R');
                  } else {
                    $pdf->SetXY(21.5, 59.6+ $step);
                    $pdf->MultiCell(10, 5, $term_beggining_y, 0, 'R');
                    $pdf->SetXY(28,59.6 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_m, 0, 'R');
                    $pdf->SetXY(34, 59.6 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_d, 0, 'R');
                  }
                }

                if (!empty($end)) {
                    $term_end_y = date('Y',strtotime($schedules7['Schedules7']['term'.$i.'_end'])) -1988;
                    $term_end_m = date('n',strtotime($schedules7['Schedules7']['term'.$i.'_end'])) ;
                    $term_end_d = date('j',strtotime($schedules7['Schedules7']['term'.$i.'_end'])) ;
                  if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                    $pdf->SetXY(21.5, 63 + $step);
                    $pdf->MultiCell(10, 5, $term_end_y, 0, 'R');
                    $pdf->SetXY(28, 63 + $step);
                    $pdf->MultiCell(10, 5, $term_end_m, 0, 'R');
                    $pdf->SetXY(34, 63 + $step);
                    $pdf->MultiCell(10, 5, $term_end_d, 0, 'R');
                  } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                    $pdf->SetXY(19, 57 + $step);
                    $pdf->MultiCell(10, 5, $term_end_y, 0, 'R');
                    $pdf->SetXY(26.5, 57 + $step);
                    $pdf->MultiCell(10, 5, $term_end_m, 0, 'R');
                    $pdf->SetXY(32.5, 57 + $step);
                    $pdf->MultiCell(10, 5, $term_end_d, 0, 'R');
                  } else {
                    $pdf->SetXY(21.5, 63.4 + $step);
                    $pdf->MultiCell(10, 5, $term_end_y, 0, 'R');
                    $pdf->SetXY(28, 63.4 + $step);
                    $pdf->MultiCell(10, 5, $term_end_m, 0, 'R');
                    $pdf->SetXY(34, 63.4 + $step);
                    $pdf->MultiCell(10, 5, $term_end_d, 0, 'R');
                  }
                }

                $pdf->SetFont($font, null, 10, true);
                $previous_balance = $schedules7['Schedules7']['previous_balance'.$i];
                if (!empty($previous_balance)) {
                    $previous_balance = number_format($previous_balance);
                    $height = (mb_strwidth($previous_balance, 'utf8') <= 14) ? 55 : 53;
                    if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                      $pdf->SetXY(95, $height + $step + 6.5);
                    } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                      $pdf->SetXY(90, $height + $step);
                    } else {
                      $pdf->SetXY(95, $height + $step + 8);
                    }
                    $pdf->MultiCell(28, 5, $previous_balance, 0, 'R');
                }

                $pdf->SetFont($font, null, 20, true);
                $class = $schedules7['Schedules7']['class'.$i];
                if (!empty($class) && !empty($previous_balance)) {
                  if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                    if ($class == '1') {
                        //青色欠損に◯
                        $pdf->SetXY(47, 58.7+$step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    } else {
                        //災害損失に◯
                        $pdf->SetXY(80, 58.7+$step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    }
                 } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                    if ($class == '1') {
                        //青色欠損に◯
                        $pdf->SetXY(44, 51.5+$step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    } else {
                        //災害損失に◯
                        $pdf->SetXY(74, 51.5+$step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    }
                  } else {
                    if ($class == '1') {
                        //青色欠損に◯
                        $pdf->SetXY(47, 59.2+$step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    } else {
                        //災害損失に◯
                        $pdf->SetXY(77, 59.2+$step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    }
                  }
                }

                $pdf->SetFont($font, null, 10, true);
                if (!empty($schedules7['this_deduction'.$i])) {
                    $this_deduction = number_format($schedules7['this_deduction'.$i]);
                    $height = (mb_strwidth($this_deduction, 'utf8') <= 14) ? 55 : 53;
                    if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                      $pdf->SetXY(130, $height + $step + 6.5);
                    } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                      $pdf->SetXY(130, $height + $step);
                    } else {
                      $pdf->SetXY(130, $height + $step + 8);
                    }
                    $pdf->MultiCell(28, 5, $this_deduction, 0, 'R');
                }

                if ($i != 9 && !empty($schedules7['next_loss'.$i])) {
                    $next_loss = number_format($schedules7['next_loss'.$i]);
                    $height = (mb_strwidth($next_loss, 'utf8') <= 14) ? 55 : 53;
                    if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                      $pdf->SetXY(165, $height + $step + 6.5);
                    } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                      $pdf->SetXY(170, $height + $step);
                    } else {
                      $pdf->SetXY(165, $height + $step + 8);
                    }
                    $pdf->MultiCell(28, 5, $next_loss, 0, 'R');
                }

                if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
                  $step += 7.1;
                } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                  $step += 8.5;
                } else {
                  $step += 8.1;
                }
            }
        }

        $previous_balance_sum = number_format($schedules7['previous_balance_sum']);
        $height = (mb_strwidth($previous_balance_sum, 'utf8') <= 14) ? 55 : 53;
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(95, $height + $step +6.5);
        } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(90, $height + $step);
        } else {
          $pdf->SetXY(95, $height + $step +8);
        }
        $pdf->MultiCell(28, 5, $previous_balance_sum, 0, 'R');

        $this_deduction_sum = number_format($schedules7['this_deduction_sum']);
        $height = (mb_strwidth($this_deduction_sum, 'utf8') <= 14) ? 55 : 53;
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(130, $height + $step +6.5);
        } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(130, $height + $step);
        } else {
          $pdf->SetXY(130, $height + $step +8);
        }
        $pdf->MultiCell(28, 5, $this_deduction_sum, 0, 'R');

        $kurikodhi_sum = number_format($schedules7['kurikodhi_sum']);
        $height = (mb_strwidth($kurikodhi_sum, 'utf8') <= 14) ? 55 : 53;
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(165, $height + $step +6.5);
        } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(170, $height + $step);
        } else {
          $pdf->SetXY(165, $height + $step +8);
        }
        $pdf->MultiCell(28, 5, $kurikodhi_sum, 0, 'R');

        $step += 8.5;
        if (isset($schedules7['touki_kessonkingaku']) && !empty($schedules7['touki_kessonkingaku'])) {
            $touki_kessonkingaku = number_format($schedules7['touki_kessonkingaku']);
            $height = (mb_strwidth($touki_kessonkingaku, 'utf8') <= 14) ? 55 : 53;
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $pdf->SetXY(95, $height + $step +5);
          } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $pdf->SetXY(90, $height + $step);
          } else {
            $pdf->SetXY(95, $height + $step +7.8);
          }
            $pdf->MultiCell(28, 5, $touki_kessonkingaku, 0, 'R');
        }

        $step += 8.5;
        if (!empty($schedules7['Schedules7']['saigai_sonsitsukin'])) {
            $saigai_sonsitsukin = number_format($schedules7['Schedules7']['saigai_sonsitsukin']);
            $height = (mb_strwidth($saigai_sonsitsukin, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(95, $height + $step+3.8);
              $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');

              $pdf->SetXY(165, $height + $step+3.8);
              $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');
            } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
              $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');

              $pdf->SetXY(170, $height + $step);
              $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');
            } else {
              $pdf->SetXY(95, $height + $step+6.9);
              $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');

              $pdf->SetXY(165, $height + $step+6.9);
              $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');
            }
          }

        $step += 8.5;
        if (isset($schedules7['aoiro']) && !empty($schedules7['aoiro'])) {
            $aoiro = number_format($schedules7['aoiro']);
            $height = (mb_strwidth($aoiro, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(95, $height + $step +2.5);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');

            } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');

            } else {
              $pdf->SetXY(95, $height + $step +6.8);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');

            }
        }

        if (isset($schedules7['aoiro_zan']) && !empty($schedules7['aoiro_zan'])) {
            $aoiro = number_format($schedules7['aoiro_zan']);
            $height = (mb_strwidth($aoiro, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(165, $height + $step+2.5);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
            } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step );
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
            } else {
              $pdf->SetXY(165, $height + $step+6.8);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
            }
        }

        if (isset($schedules7['Schedules7']['aoiro_kurimodoshi']) && !empty($schedules7['Schedules7']['aoiro_kurimodoshi'])) {
            $aoiro = number_format($schedules7['Schedules7']['aoiro_kurimodoshi']);
            $height = (mb_strwidth($aoiro, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(130, $height + $step+2.5);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
            } elseif(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(130, $height + $step );
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
            } else {
              $pdf->SetXY(130, $height + $step+6.8);
              $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
            }
        }

        $step += 8.5;
        if (isset($schedules7['aoiro_sum']) && !empty($schedules7['aoiro_sum'])) {
            $aoiro_sum = number_format($schedules7['aoiro_sum']);
            $height = (mb_strwidth($aoiro_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(165, $height + $step +1);
            } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step +6);
            }
            $pdf->MultiCell(28, 5, $aoiro_sum, 0, 'R');
        }

        $step += 16;
        $saigai_shurui = $schedules7['Schedules7']['saigai_shurui'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $x = array('x1' => 85.1, 'x2' => 90.3);
          $y = array('y1' => 170, 'y2' => 168.2, 'y3' => null);
          $align = array('align1' => 'C', 'align2' => 'L');
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $x = array('x1' => 81.1, 'x2' => 85.1);
          $y = array('y1' => 181.4, 'y2' => 180, 'y3' => null);
          $align = array('align1' => 'C', 'align2' => 'L');
        } else {
          $x = array('x1' => 85.1, 'x2' => 90);
          $y = array('y1' => 183.4, 'y2' => 181.8, 'y3' => null);
          $align = array('align1' => 'C', 'align2' => 'L');
        }
        $this->_putBaseStringWithLimit($pdf, $font, 7.5, $saigai_shurui, 2, 26, 3.2, 47, $x, $y, $align);


        $pdf->SetFont($font, null, 10, true);
        $saigai_yandahi = $schedules7['Schedules7']['saigai_yandahi'];
        if (!empty($saigai_yandahi)) {
            $saigai_yandahi_y = date('Y',strtotime($saigai_yandahi)) -1988;
            $saigai_yandahi_m = date('n',strtotime($saigai_yandahi)) ;
            $saigai_yandahi_d = date('j',strtotime($saigai_yandahi)) ;

          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $pdf->SetXY(161, 56 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_y, 0, 'R');
            $pdf->SetXY(170.7, 56 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_m, 0, 'R');
            $pdf->SetXY(181.5, 56 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_d, 0, 'R');
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $pdf->SetXY(165, 54.5 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_y, 0, 'R');
            $pdf->SetXY(175, 54.5 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_m, 0, 'R');
            $pdf->SetXY(186, 54.5 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_d, 0, 'R');
          } else {
            $pdf->SetXY(163, 60 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_y, 0, 'R');
            $pdf->SetXY(173, 60 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_m, 0, 'R');
            $pdf->SetXY(183, 60 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_d, 0, 'R');
          }
        }

        $step += 23;
        if (isset($schedules7['toukinokessonkingaku']) && !empty($schedules7['toukinokessonkingaku'])) {
            $toukinokessonkingaku = number_format($schedules7['toukinokessonkingaku']);
            $height = (mb_strwidth($toukinokessonkingaku, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(165, $height + $step - 3);
            } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step + 3.8);
            }
            $pdf->MultiCell(28, 5, $toukinokessonkingaku, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 4;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 12;
        }
        if (!empty($schedules7['Schedules7']['tana_shisannomesshitsu'])) {
            $tana_shisannomesshitsu = number_format($schedules7['Schedules7']['tana_shisannomesshitsu']);
            $height = (mb_strwidth($tana_shisannomesshitsu, 'utf8') <= 14) ? 55 : 53;
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $pdf->SetXY(95, $height + $step);
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $pdf->SetXY(90, $height + $step);
          } else {
            $pdf->SetXY(95, $height + $step);
          }
            $pdf->MultiCell(28, 5, $tana_shisannomesshitsu, 0, 'R');
        }

        if (!empty($schedules7['Schedules7']['kotei_shisannomesshitsu'])) {
            $kotei_shisannomesshitsu = number_format($schedules7['Schedules7']['kotei_shisannomesshitsu']);
            $height = (mb_strwidth($kotei_shisannomesshitsu, 'utf8') <= 14) ? 55 : 53;
            $pdf->SetXY(130, $height + $step);
            $pdf->MultiCell(28, 5, $kotei_shisannomesshitsu, 0, 'R');
        }

        if (!empty($schedules7['shisannomesshitsu_sum'])) {
            $shisannomesshitsu_sum = number_format($schedules7['shisannomesshitsu_sum']);
            $height = (mb_strwidth($shisannomesshitsu_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $shisannomesshitsu_sum, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 8;
        }
        if (!empty($schedules7['Schedules7']['tana_higaishisannogenjyoukaifuku'])) {
            $tana_higaishisannogenjyoukaifuku = number_format($schedules7['Schedules7']['tana_higaishisannogenjyoukaifuku']);
            $height = (mb_strwidth($tana_higaishisannogenjyoukaifuku, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
            } else {
              $pdf->SetXY(95, $height + $step);
            }
            $pdf->MultiCell(28, 5, $tana_higaishisannogenjyoukaifuku, 0, 'R');
        }

        if (!empty($schedules7['Schedules7']['kotei_higaishisannogenjyoukaifuku'])) {
            $kotei_higaishisannogenjyoukaifuku = number_format($schedules7['Schedules7']['kotei_higaishisannogenjyoukaifuku']);
            $height = (mb_strwidth($kotei_higaishisannogenjyoukaifuku, 'utf8') <= 14) ? 55 : 53;
            $pdf->SetXY(130, $height + $step);
            $pdf->MultiCell(28, 5, $kotei_higaishisannogenjyoukaifuku, 0, 'R');
        }

        if (!empty($schedules7['higaishisannogenjyou_sum'])) {
            $higaishisannogenjyou_sum = number_format($schedules7['higaishisannogenjyou_sum']);
            $height = (mb_strwidth($higaishisannogenjyou_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $higaishisannogenjyou_sum, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 8.25;
        }
        if (!empty($schedules7['Schedules7']['tana_higainokakudai'])) {
            $tana_higainokakudai = number_format($schedules7['Schedules7']['tana_higainokakudai']);
            $height = (mb_strwidth($tana_higainokakudai, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
            } else {
              $pdf->SetXY(95, $height + $step);
            }
            $pdf->MultiCell(28, 5, $tana_higainokakudai, 0, 'R');
        }

        if (!empty($schedules7['Schedules7']['kotei_higainokakudai'])) {
            $kotei_higainokakudai = number_format($schedules7['Schedules7']['kotei_higainokakudai']);
            $height = (mb_strwidth($kotei_higainokakudai, 'utf8') <= 14) ? 55 : 53;
            $pdf->SetXY(130, $height + $step);
            $pdf->MultiCell(28, 5, $kotei_higainokakudai, 0, 'R');
        }

        if (!empty($schedules7['higainokakudai_sum'])) {
            $higainokakudai_sum = number_format($schedules7['higainokakudai_sum']);
            $height = (mb_strwidth($higainokakudai_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $higainokakudai_sum, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 8.15;
        }
        if (!empty($schedules7['tana_sum'])) {
            $tana_sum = number_format($schedules7['tana_sum']);
            $height = (mb_strwidth($tana_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
            } else {
              $pdf->SetXY(95, $height + $step);
            }
            $pdf->MultiCell(28, 5, $tana_sum, 0, 'R');
        }

        if (!empty($schedules7['kotei_sum'])) {
            $kotei_sum = number_format($schedules7['kotei_sum']);
            $height = (mb_strwidth($kotei_sum, 'utf8') <= 14) ? 55 : 53;
            $pdf->SetXY(130, $height + $step);
            $pdf->MultiCell(28, 5, $kotei_sum, 0, 'R');
        }

        if (!empty($schedules7['saigai_sonshitsu_sum'])) {
            $saigai_sonshitsu_sum = number_format($schedules7['saigai_sonshitsu_sum']);
            $height = (mb_strwidth($saigai_sonshitsu_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $saigai_sonshitsu_sum, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7.2;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 8.05;
        }
        if (!empty($schedules7['Schedules7']['tana_hokenkin'])) {
            $tana_hokenkin = number_format($schedules7['Schedules7']['tana_hokenkin']);
            $height = (mb_strwidth($tana_hokenkin, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
            } else {
              $pdf->SetXY(95, $height + $step);
            }
            $pdf->MultiCell(28, 5, $tana_hokenkin, 0, 'R');
        }

        if (!empty($schedules7['Schedules7']['kotei_hokenkin'])) {
            $kotei_hokenkin = number_format($schedules7['Schedules7']['kotei_hokenkin']);
            $height = (mb_strwidth($kotei_hokenkin, 'utf8') <= 14) ? 55 : 53;
            $pdf->SetXY(130, $height + $step);
            $pdf->MultiCell(28, 5, $kotei_hokenkin, 0, 'R');
        }

        if (!empty($schedules7['hokenkin_sum'])) {
            $hokenkin_sum = number_format($schedules7['hokenkin_sum']);
            $height = (mb_strwidth($hokenkin_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $hokenkin_sum, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7.2;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 8.05;
        }

        if (!empty($schedules7['tana_sashihiki'])) {
            $tana_sashihiki = number_format($schedules7['tana_sashihiki']);
            $height = (mb_strwidth($tana_sashihiki, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(90, $height + $step);
            } else {
              $pdf->SetXY(95, $height + $step);
            }
            $pdf->MultiCell(28, 5, $tana_sashihiki, 0, 'R');
        }

        if (!empty($schedules7['kotei_sashihiki'])) {
            $kotei_sashihiki = number_format($schedules7['kotei_sashihiki']);
            $height = (mb_strwidth($kotei_sashihiki, 'utf8') <= 14) ? 55 : 53;
            $pdf->SetXY(130, $height + $step);
            $pdf->MultiCell(28, 5, $kotei_sashihiki, 0, 'R');
        }

        if (!empty($schedules7['sashihiki_sum'])) {
            $sashihiki_sum = number_format($schedules7['sashihiki_sum']);
            $height = (mb_strwidth($sashihiki_sum, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $sashihiki_sum, 0, 'R');
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7.2;
          if (!empty($schedules7['Schedules7']['shotokuzei_kanpu'])) {
              $tana_sashihiki = number_format($schedules7['Schedules7']['shotokuzei_kanpu']);
              $height = (mb_strwidth($tana_sashihiki, 'utf8') <= 14) ? 55 : 53;
                $pdf->SetXY(95, $height + $step);

              $pdf->MultiCell(28, 5, $tana_sashihiki, 0, 'R');
          }

          if (!empty($schedules7['Schedules7']['shotokuzei_kanpu2'])) {
              $kotei_sashihiki = number_format($schedules7['Schedules7']['shotokuzei_kanpu2']);
              $height = (mb_strwidth($kotei_sashihiki, 'utf8') <= 14) ? 55 : 53;
              $pdf->SetXY(130, $height + $step);
              $pdf->MultiCell(28, 5, $kotei_sashihiki, 0, 'R');
          }

          if (!empty($schedules7['Schedules7']['shotokuzei_kanpu_sum'])) {
              $sashihiki_sum = number_format($schedules7['Schedules7']['shotokuzei_kanpu_sum']);
              $height = (mb_strwidth($sashihiki_sum, 'utf8') <= 14) ? 55 : 53;
                $pdf->SetXY(165, $height + $step);
              $pdf->MultiCell(28, 5, $sashihiki_sum, 0, 'R');
          }

          $step += 7.2;
          if (!empty($schedules7['Schedules7']['chukan_kurimodoshi'])) {
              $chukan_kurimodoshi = number_format($schedules7['Schedules7']['chukan_kurimodoshi']);
              $height = (mb_strwidth($chukan_kurimodoshi, 'utf8') <= 14) ? 55 : 53;
                $pdf->SetXY(165, $height + $step);
              $pdf->MultiCell(28, 5, $chukan_kurimodoshi, 0, 'R');
          }

          $step += 7.2;
          if (!empty($schedules7['Schedules7']['kurimodoshi_taisho'])) {
              $kurimodoshi_taisho = number_format($schedules7['Schedules7']['kurimodoshi_taisho']);
              $height = (mb_strwidth($kurimodoshi_taisho, 'utf8') <= 14) ? 55 : 53;
              $pdf->SetXY(165, $height + $step);
              $pdf->MultiCell(28, 5, $kurimodoshi_taisho, 0, 'R');
          }
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $step += 7.2;
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $step += 8.75;
        } else {
          $step += 8.05;
        }
        if (!empty($schedules7['kurikoshikoujyosonshitsu'])) {
            $kurikoshikoujyosonshitsu = number_format($schedules7['kurikoshikoujyosonshitsu']);
            $height = (mb_strwidth($kurikoshikoujyosonshitsu, 'utf8') <= 14) ? 55 : 53;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_end'])){
              $pdf->SetXY(170, $height + $step);
            } else {
              $pdf->SetXY(165, $height + $step);
            }
            $pdf->MultiCell(28, 5, $kurikoshikoujyosonshitsu, 0, 'R');
        }

        return $pdf;
    }

    /**
     * 固定資産
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param $search_words
     * @return FPDI OBJ $pdf
     */
    function export_fixed_assets($pdf, $font, $search_words = array()) {
        $period_id   = CakeSession::read('Auth.User.term_id');
        $user_id     = CakeSession::read('Auth.User.id');

        // 決算日IDとユーザIDが必須
        if(empty($period_id) || empty($user_id)){
          throw new Exception("PDF出力に必要なデータが足りません");
        }

        // 計算期間の出力
        $Term = ClassRegistry::init('Term');
        $this->Controller->set('period', $Term->find('first', array('conditions' => array('id' => $period_id), 'recursive' => -1)));

        // ユーザ情報取得
        $User = ClassRegistry::init('User');
        $user = $User->find('first', array('conditions' => array('User.id' => $user_id), 'recursive' => 0));
        $this->Controller->set('user', $user);
        $this->Controller->set('name', $user['name']);

        // 少額資産取得
        if ($user['User']['plan'] == '2' and $user['User']['return_class'] == '1') {
          $SmallAsset = ClassRegistry::init('SmallAsset');;
          $small_asset_datas = $SmallAsset->get_display_pdf($user_id, $period_id);
          $this->Controller->set('small_asset_datas', $small_asset_datas);
        }

        $FixedAsset = ClassRegistry::init('FixedAsset');
        $method = CakeSession::read('Auth.User.accounting_method');
        if (CakeSession::read('Auth.User.plan') == 4) {
            $AccountInfo = ClassRegistry::init('AccountInfo');
            $account_info = $AccountInfo->find('first', array('conditions' => array('user_id' => $user_id, 'term_id' => $period_id), 'recursive' => -1));
            $method = $account_info['AccountInfo']['accounting_method'];
        }
        $datas = $FixedAsset->get_display_pdf($user_id, $period_id, $method, $search_words);
        $this->Controller->set('datas', $datas);

        // 1ページの行数
        define('PDF_PAGE_LIMIT', 14);
        // 1行進める改行数
        define('PDF_LINE_KAIGYO', 4);

        $max_page =  floor((string)$datas['count'] / PDF_PAGE_LIMIT) + 1; //ページ数
        $this->Controller->set('max_page', $max_page);

        // helper読込
        $this->Controller->helpers[] = 'Pdf';
        // View設定
        $this->Controller->layout = 'pdf';
        // 日本語対応＆A4横で出力
        $this->Mpdf->init(array('mode'=>'ja', 'format'=>'A4-L'));

        $pdf_name = TMP.time().'.pdf';
        $this->Mpdf->setFilename($pdf_name);
        // ファイルに出力
        $this->Mpdf->setOutput('F');
        $this->Controller->render('/FixedAssets/export_pdf');
        $html = (string)$this->Controller->response;
        $this->Mpdf->outPut($html);
        // ２重に出力されないように出力タイプ変更
        $this->Mpdf->setOutput('S');

        //テンプレート読込
        $pageCount = $pdf->setSourceFile($pdf_name);
        for ($pageNo = 1;  $pageNo <= $pageCount; $pageNo++) {
            $template = $pdf->importPage($pageNo);
            //ページ追加
            $pdf->AddPage('L');
            $pdf->useTemplate($template, null, null, null, null, true);
        }

        // ファイル削除
        unlink($pdf_name);

        return $pdf;
    }

    /**
     * 欠損金額等の控除明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_prefecture9($pdf, $font) {

      $Schedules7 = ClassRegistry::init('Schedules7');
      $FixedAsset = ClassRegistry::init('FixedAsset');
      $Schedules4 = ClassRegistry::init('Schedules4');
      $Schedules14 = ClassRegistry::init('Schedules14');
      $Schedules168 = ClassRegistry::init('Schedules168');
      $Term = ClassRegistry::init('Term');

      //事業年度で様式選択
      $term_info = $Term->getCurrentTerm();
      $target_day = '2016/04/01';
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'prefecture_6_9.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 'prefecture_6_9_h28.pdf');
      }

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $schedules7 = $Schedules7->findFor6_9($preSum, $data14['not_cost']);


        $term_id = CakeSession::read('Auth.User.term_id');

        $pdf->SetFont($font, null, 12, true);

        $term = $Term->find('first', array(
            'conditions' => array('Term.id' => $term_id,
        )));

        $y1 = date('Y', strtotime($term['Term']['account_beggining'])) - 1988;
        $m1 = date('n', strtotime($term['Term']['account_beggining']));
        $d1 = date('j', strtotime($term['Term']['account_beggining']));

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(96.5, 14.3);
        $pdf->MultiCell(20, 5, $y1, 0, 'C');
        $pdf->SetXY(107.5, 14.3);
        $pdf->MultiCell(20, 5, $m1, 0, 'C');
        $pdf->SetXY(117.5, 14.3);
        $pdf->MultiCell(20, 5, $d1, 0, 'C');

        $y2 = date('Y', strtotime($term['Term']['account_end'])) - 1988;
        $m2 = date('n', strtotime($term['Term']['account_end']));
        $d2 = date('j', strtotime($term['Term']['account_end']));

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(96.5, 20);
        $pdf->MultiCell(20, 5, $y2, 0, 'C');
        $pdf->SetXY(107.5, 20);
        $pdf->MultiCell(20, 5, $m2, 0, 'C');
        $pdf->SetXY(117.5, 20);
        $pdf->MultiCell(20, 5, $d2, 0, 'C');

        // 名称
        $pdf->SetFont($font, null, 9, true);
        $user_name = $schedules7['name'];
        $x = array('x1' => 146, 'x2' => 146);
        $y = array('y1' => 17.5, 'y2' => 15.5, 'y3' => 13.5);
        $align = array('align1' => 'C', 'align2' => 'L');
        $this->_putBaseStringWithLimit($pdf, $font, 9, $user_name, 3, 28, 3.2, 47, $x, $y, $align);

        $pdf->SetFont($font, null, 10, true);

        if (!empty($schedules7['pre_shotoku'])) {
            $pre_shotoku = number_format($schedules7['pre_shotoku']);
            $pre_shotoku = str_replace('-', '△', $pre_shotoku);
            $total_limit = number_format($schedules7['total_limit']);
            $height = (mb_strwidth($pre_shotoku, 'utf8') <= 14) ? 32 : 30;
            $pdf->SetXY(73, $height);
            $pdf->MultiCell(28, 5, $pre_shotoku, 0, 'R');
            $pdf->SetXY(160, $height);
            $pdf->MultiCell(28, 5, $total_limit, 0, 'R');
        }

        $step = 0;
        if (!empty($schedules7['Schedules7'])) {
            for ($i = 9; $i >= 1; $i--) {
                $pdf->SetFont($font, null, 10, true);
                $beggining = $schedules7['Schedules7']['term' . $i . '_beggining'];
                $end = $schedules7['Schedules7']['term' . $i . '_end'];
                if (!empty($beggining)) {
                    $term_beggining_y = date('Y', strtotime($schedules7['Schedules7']['term' . $i . '_beggining'])) - 1988;
                    $term_beggining_m = date('n', strtotime($schedules7['Schedules7']['term' . $i . '_beggining']));
                    $term_beggining_d = date('j', strtotime($schedules7['Schedules7']['term' . $i . '_beggining']));

                    $pdf->SetXY(19.5, 52.75 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_y, 0, 'R');
                    $pdf->SetXY(26.5, 52.75 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_m, 0, 'R');
                    $pdf->SetXY(33.5, 52.75 + $step);
                    $pdf->MultiCell(10, 5, $term_beggining_d, 0, 'R');
                }

                if (!empty($end)) {
                    $term_end_y = date('Y', strtotime($schedules7['Schedules7']['term' . $i . '_end'])) - 1988;
                    $term_end_m = date('n', strtotime($schedules7['Schedules7']['term' . $i . '_end']));
                    $term_end_d = date('j', strtotime($schedules7['Schedules7']['term' . $i . '_end']));

                    $pdf->SetXY(19.5, 58.5 + $step);
                    $pdf->MultiCell(10, 5, $term_end_y, 0, 'R');
                    $pdf->SetXY(26.5, 58.5 + $step);
                    $pdf->MultiCell(10, 5, $term_end_m, 0, 'R');
                    $pdf->SetXY(33.5, 58.5 + $step);
                    $pdf->MultiCell(10, 5, $term_end_d, 0, 'R');
                }

                $pdf->SetFont($font, null, 22.6, true);
                $class = $schedules7['Schedules7']['class' . $i];
                if (!empty($class)) {
                    if ($class == '1') {
                        //欠損金額等に◯
                        $pdf->SetXY(57, 52.5 + $step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    } else {
                        //災害損失金に◯
                        $pdf->SetXY(71, 52.5 + $step);
                        $pdf->MultiCell(10, 5, '◯', 0, 'C');
                    }
                }

                $pdf->SetFont($font, null, 10, true);
                $previous_balance = $schedules7['Schedules7']['previous_balance' . $i];
                if (!empty($previous_balance)) {
                    $previous_balance = number_format($previous_balance);
                    $height = (mb_strwidth($previous_balance, 'utf8') <= 14) ? 56 : 54.5;
                    $pdf->SetXY(89, $height + $step);
                    $pdf->MultiCell(28, 5, $previous_balance, 0, 'R');
                }

                $this_deduction = $schedules7['this_deduction' . $i];
                if (!empty($this_deduction)) {
                    $this_deduction = number_format($this_deduction);
                    $height = (mb_strwidth($this_deduction, 'utf8') <= 14) ? 56 : 54.5;
                    $pdf->SetXY(125, $height + $step);
                    $pdf->MultiCell(28, 5, $this_deduction, 0, 'R');
                }

                if ($i != 9 && !empty($schedules7['next_loss' . $i])) {
                    $next_loss = number_format($schedules7['next_loss' . $i]);
                    $height = (mb_strwidth($next_loss, 'utf8') <= 14) ? 56 : 54.5;
                    $pdf->SetXY(160, $height + $step);
                    $pdf->MultiCell(28, 5, $next_loss, 0, 'R');
                }


                $step += 11.86;
            }
        }

        $previous_balance_sum = $schedules7['previous_balance_sum'];
        $previous_balance_sum = number_format($previous_balance_sum);
        $height = (mb_strwidth($previous_balance_sum, 'utf8') <= 14) ? 56 : 54.5;
        $pdf->SetXY(89, $height + $step);
        $pdf->MultiCell(28, 5, $previous_balance_sum, 0, 'R');

        $this_deduction_sum = $schedules7['this_deduction_sum'];
        $this_deduction_sum = number_format($this_deduction_sum);
        $height = (mb_strwidth($this_deduction_sum, 'utf8') <= 14) ? 56 : 54.5;
        $pdf->SetXY(125, $height + $step);
        $pdf->MultiCell(28, 5, $this_deduction_sum, 0, 'R');

        $kurikoshi_sum = $schedules7['kurikoshi_sum'];
        $kurikoshi_sum = number_format($kurikoshi_sum);
        $height = (mb_strwidth($kurikoshi_sum, 'utf8') <= 14) ? 56 : 54.5;
        $pdf->SetXY(160, $height + $step);
        $pdf->MultiCell(28, 5, $kurikoshi_sum, 0, 'R');

        $step += 11.86;
        if (isset($schedules7['touki_kessonkingaku']) && !empty($schedules7['touki_kessonkingaku'])) {
            $touki_kessonkingaku = number_format($schedules7['touki_kessonkingaku']);
            $height = (mb_strwidth($touki_kessonkingaku, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(89, $height + $step);
            $pdf->MultiCell(28, 5, $touki_kessonkingaku, 0, 'R');
        }

        $step += 11.86;
        if (!empty($schedules7['Schedules7']['saigai_sonsitsukin'])) {
            $saigai_sonsitsukin = number_format($schedules7['Schedules7']['saigai_sonsitsukin']);
            $height = (mb_strwidth($saigai_sonsitsukin, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(89, $height + $step);
            $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');

            $pdf->SetXY(160, $height + $step);
            $pdf->MultiCell(28, 5, $saigai_sonsitsukin, 0, 'R');
        }

        $step += 11.86;
        if (isset($schedules7['aoiro']) && !empty($schedules7['aoiro'])) {
            $aoiro = number_format($schedules7['aoiro']);
            $height = (mb_strwidth($aoiro, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(89, $height + $step);
            $pdf->MultiCell(28, 5, $aoiro, 0, 'R');

            $pdf->SetXY(160, $height + $step);
            $pdf->MultiCell(28, 5, $aoiro, 0, 'R');
        }

        $step += 11.86;
        if (isset($schedules7['aoiro_sum']) && !empty($schedules7['aoiro_sum'])) {
            $aoiro_sum = number_format($schedules7['aoiro_sum']);
            $height = (mb_strwidth($aoiro_sum, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(160, $height + $step);
            $pdf->MultiCell(28, 5, $aoiro_sum, 0, 'R');
        }

        $step += 21;
        $saigai_shurui = $schedules7['Schedules7']['saigai_shurui'];
        $x = array('x1' => 59.5, 'x2' => 60.8);
        $y = array('y1' => 230.2, 'y2' => 228.8, 'y3' => null);
        $align = array('align1' => 'C', 'align2' => 'L');
        $this->_putBaseStringWithLimit($pdf, $font, 8.5, $saigai_shurui, 2, 28, 3.2, 47, $x, $y, $align);

        $pdf->SetFont($font, null, 10, true);
        $saigai_yandahi = $schedules7['Schedules7']['saigai_yandahi'];
        if (!empty($saigai_yandahi)) {
            $saigai_yandahi_y = date('Y', strtotime($saigai_yandahi)) - 1988;
            $saigai_yandahi_m = date('n', strtotime($saigai_yandahi));
            $saigai_yandahi_d = date('j', strtotime($saigai_yandahi));

            $pdf->SetXY(156, 54.5 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_y, 0, 'R');
            $pdf->SetXY(166, 54.5 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_m, 0, 'R');
            $pdf->SetXY(176, 54.5 + $step);
            $pdf->MultiCell(10, 5, $saigai_yandahi_d, 0, 'R');
        }

        $step += 11.86;
        if (isset($schedules7['toukinokessonkingaku']) && !empty($schedules7['toukinokessonkingaku'])) {
            $toukinokessonkingaku = number_format($schedules7['toukinokessonkingaku']);
            $height = (mb_strwidth($toukinokessonkingaku, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(75, $height + $step);
            $pdf->MultiCell(28, 5, $toukinokessonkingaku, 0, 'R');
        }
        if (!empty($schedules7['sashihiki_sum'])) {
            $sashihiki_sum = number_format($schedules7['sashihiki_sum']);
            $height = (mb_strwidth($sashihiki_sum, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(160, $height + $step);
            $pdf->MultiCell(28, 5, $sashihiki_sum, 0, 'R');
        }

        $step += 11.86;

        if (!empty($schedules7['saigai_sonshitsu_sum'])) {
            $saigai_sonshitsu_sum = number_format($schedules7['saigai_sonshitsu_sum']);
            $height = (mb_strwidth($saigai_sonshitsu_sum, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(75, $height + $step);
            $pdf->MultiCell(28, 5, $saigai_sonshitsu_sum, 0, 'R');
        }

        if (isset($schedules7['kurikoshikoujyosonshitsu']) && !empty($schedules7['kurikoshikoujyosonshitsu'])) {
            $kurikoshikoujyosonshitsu = number_format($schedules7['kurikoshikoujyosonshitsu']);
            $height = (mb_strwidth($kurikoshikoujyosonshitsu, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(160, $height + $step);
            $pdf->MultiCell(28, 5, $kurikoshikoujyosonshitsu, 0, 'R');
        }

        $step += 11.86;
        if (!empty($schedules7['hokenkin_sum'])) {
            $hokenkin_sum = number_format($schedules7['hokenkin_sum']);
            $height = (mb_strwidth($hokenkin_sum, 'utf8') <= 14) ? 56 : 54.5;
            $pdf->SetXY(75, $height + $step);
            $pdf->MultiCell(28, 5, $hokenkin_sum, 0, 'R');
        }
        return $pdf;
    }

    /**
     * 少額資産台帳
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_small_assets($pdf, $font) {

        $Term        = ClassRegistry::init('Term');
        $SmallAsset  = ClassRegistry::init('SmallAsset');
        $period_id   = CakeSession::read('Auth.User.term_id');
        $user_id     = CakeSession::read('Auth.User.id');

        // 計算期間の出力
        $this->Controller->set('period', $Term->find('first', array('conditions' => array('id' => $period_id), 'recursive' => -1)));

        // ユーザ情報取得
        $this->Controller->set('name', CakeSession::read('Auth.User.name'));

        $datas = $SmallAsset->get_display_pdf($user_id, $period_id);
        $this->Controller->set('datas', $datas);

        // 1ページの行数
        define('PDF_PAGE_LIMIT', 19);
        // 1行進める改行数
        define('PDF_LINE_KAIGYO', 3);
        $max_page =  ceil($datas['count'] / PDF_PAGE_LIMIT); //ページ数
        $this->Controller->set('max_page', $max_page);

        // helper読込
        $this->Controller->helpers[] = 'Pdf';

        // View設定
        $this->Controller->layout = 'pdf';
        // 日本語対応＆A4横で出力
        $this->Mpdf->init(array('mode'=>'ja', 'format'=>'A4-L', 'margin_left'=>25, 'margin_right'=>25));

        // ファイル名
        $pdf_name = TMP.time().'.pdf';
        $this->Mpdf->setFilename($pdf_name);
        // ファイルに出力
        $this->Mpdf->setOutput('F');
        $this->Controller->render('/SmallAssets/export_pdf');
        $html = (string)$this->Controller->response;
        $this->Mpdf->outPut($html);
        // ２重に出力されないように出力タイプ変更
        $this->Mpdf->setOutput('S');

        //テンプレート読込
        $pageCount = $pdf->setSourceFile($pdf_name);
        for ($pageNo = 1;  $pageNo <= $pageCount; $pageNo++) {
            $template = $pdf->importPage($pageNo);
            //ページ追加
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
        }

        // ファイル削除
        unlink($pdf_name);

        return $pdf;
    }

    /**
     * 別表16⑺PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules1607s($pdf, $font) {

        $user = CakeSession::read('Auth.User');
        $AccountTitle = ClassRegistry::init('AccountTitle');
        $SmallAsset = ClassRegistry::init('SmallAsset');
        $small_assets = $SmallAsset->get_display_pdf($user['id'], $user['term_id']);


        //ページ追加
        $pdf->AddPage();
        //テンプレート読込
        //事業年度で様式選択
        $term_info = $SmallAsset->getCurrentTerm();
        $target_day = '2016/01/01';
        $target_day29 = '2017/04/01';
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->setSourceFile(WWW_ROOT. 'pdf'. DS. 'schedules16_7_e290401.pdf');
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->setSourceFile(WWW_ROOT. 'pdf'. DS. 'e270401_schedules16_7.pdf');
        } else {
          $pdf->setSourceFile(WWW_ROOT. 'pdf'. DS. 's280101schedules16_7.pdf');
        }



        $template = $pdf->importPage(1);
        $pdf->useTemplate($template, null, null, null, null, true);
        $account_titles = $AccountTitle->find('list', array('fields' => array('id', 'account_title')));

        $point_start_y = 12;
        $point_step    = 11;
        $point_y       = 12;
        $height        = 35;
        $point_block_step = 77.5;

        $acc_bgn_x     = 122;
        $fullname_x    = 153.5;
        $col1_x_start  = 86;
        $col1_x        = 86;
        $total_x       = 167;
        $col_step      = 22.85;

        $record_count = 0;
        $each_blocl_count = 0;

        $total_page_cost = 0;

        $small_assets_count = $small_assets['count'];
        $small_assets_total = $small_assets['total'];
        unset($small_assets['count']);
        unset($small_assets['total']);
        foreach ($small_assets as $data) {
            $record_count++;
            $each_blocl_count++;
            $pdf->SetFont($font, null, 7, true);

            // account title
            $acc_title = isset($data['account_title_id']) ? $data['account_title'] : '';
            $height = (mb_strwidth($acc_title, 'utf8') <= 14) ? $point_y + 15.8 : $point_y + 13.8;
            $pdf->SetXY($col1_x+0.8, $height);
            $pdf->MultiCell(20, 5, $acc_title, 0, 'C');

            // small asset name
            $name = $data['name'];
            $height = (mb_strwidth($name, 'utf8') <= 14) ? $point_y + 38.5 : $point_y + 34.5;
            $pdf->SetXY($col1_x, $height);
            $pdf->MultiCell(20, 5, $name, 0, 'L');

            // buy date
            $_buy_date = explode('-', h($data['buy_date']));
            $height = $point_y + 48.5;
            $buy_date = '平成' . $this->heisei($_buy_date[0]) . '年' . (int)$_buy_date[1] . '月';
            $pdf->SetXY($col1_x, $height);
            $pdf->MultiCell(22, 5, $buy_date, 0, 'C');

            // cost num.5
            $cost = $data['cost'];
            $height = $point_y + 59.5;
            $pdf->SetXY($col1_x, $height);
            $pdf->MultiCell(22, 5, number_format($cost), 0, 'C');

            // cost num.7
            $cost = $data['cost'];
            $height = $point_y + 81.5;
            $pdf->SetXY($col1_x, $height);
            $pdf->MultiCell(22, 5, number_format($cost), 0, 'C');
            $total_page_cost += $cost;

            $col1_x += $col_step;
            if ($each_blocl_count == 5) {
                $col1_x = $col1_x_start;
                $point_y += $point_block_step;
                $each_blocl_count = 0;
            }

            if (0 < ($record_count % Configure::read('SMALL_ASSETS_PDF_ROW'))) {

            } else {

                $this->_putTotalCost($pdf, $point_start_y, $total_x, $total_page_cost);
                $pdf->SetFont($font, null, 9, true);
                $this->_putHeader($pdf, $point_start_y, $acc_bgn_x, $fullname_x, $user);
                $total_page_cost = 0;
                // ページ追加
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $point_y = $point_start_y;
            }
        }


        if (0 < ($record_count % Configure::read('SMALL_ASSETS_PDF_ROW'))) {
            $this->_putTotalCost($pdf, $point_start_y, $total_x, $total_page_cost);
            $pdf->SetFont($font, null, 9, true);
            $this->_putHeader($pdf, $point_start_y, $acc_bgn_x, $fullname_x, $user);
        }

        return $pdf;
    }

    /**
     * Calculate Heisei year
     *
     * @param int $year
     * @return int
     */
    private function heisei($year) {
        if (!is_numeric($year)) {
            return $year;
        }

        return (int)$year - 1989 + 1;
    }

    /**
     * Put user name and account beginning/end date
     *
     * @param FPDI OBJ $pdf
     * @param int $point_y
     * @param int $acc_bgn_x
     * @param int $fullname_x
     * @param array $user
     */
    private function _putHeader(&$pdf, $point_y, $acc_bgn_x, $fullname_x, $user)
    {
        // account beginning
        $Term = ClassRegistry::init('Term');
        $term_data = $Term->find('first',array(
            'conditions' => array('id' => $user['term_id']),
            'fields' => array('Term.account_beggining','Term.account_end'),
    				'recursive' => -1,
            ));
        $acc_beginning = explode('-', h($term_data['Term']['account_beggining']));
        $height = $point_y;
        $pdf->SetXY($acc_bgn_x, $height);
        $pdf->MultiCell(20, 5, isset($acc_beginning[0]) ? $this->heisei($acc_beginning[0]) : '', 0, 'L');
        $pdf->SetXY($acc_bgn_x+7.4, $height);
        $pdf->MultiCell(20, 5, isset($acc_beginning[1]) ? (int)$acc_beginning[1] : '', 0, 'L');
        $pdf->SetXY($acc_bgn_x+13, $height);
        $pdf->MultiCell(20, 5, isset($acc_beginning[2]) ? (int)$acc_beginning[2] : '', 0, 'L');

        // account end
        $acc_end = explode('-', h($term_data['Term']['account_end']));
        $height = $point_y + 5.6;
        $pdf->SetXY($acc_bgn_x, $height);
        $pdf->MultiCell(20, 5, isset($acc_end[0]) ? $this->heisei($acc_end[0]) : '', 0, 'L');
        $pdf->SetXY($acc_bgn_x+7.4, $height);
        $pdf->MultiCell(20, 5, isset($acc_end[1]) ? (int)$acc_end[1] : '', 0, 'L');
        $pdf->SetXY($acc_bgn_x+13, $height);
        $pdf->MultiCell(20, 5, isset($acc_end[2]) ? (int)$acc_end[2] : '', 0, 'L');

        // user full name
        //$fullname = mb_strimwidth($user['name'], 0, 56, '...', 'utf-8');
        $fullname = substr($user['name'], 0, 84);
        $height = (mb_strwidth($fullname, 'utf8') <= 28) ? $point_y + 3.3 : $point_y - 0.3;
        $pdf->SetXY($fullname_x, $height);
        $pdf->MultiCell(48, 5, $fullname, 0, 'L');
    }

    /**
     * Put total cost to each page
     *
     * @param FPDI OBJ $pdf
     * @param int $point_y
     * @param int $total_x
     * @param float $total
     */
    private function _putTotalCost(&$pdf, $point_y, $total_x, $total)
    {
        $height = $point_y + 248;
        $pdf->SetXY($total_x, $height);
        $pdf->MultiCell(20, 5, number_format($total), 0, 'C');
    }

    /**
     * 普通法人等の申告書OCR PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules0101ocrs($pdf, $font) {
        $user = CakeSession::read('Auth.User');

        $Schedules1 = ClassRegistry::init('Schedules1');

        //事業年度で様式選択
        $term_info = $Schedules1->getCurrentTerm();
        $target_day = '2016/01/01';
        $target_day29 = '2017/04/01';

        $FixedAsset = ClassRegistry::init('FixedAsset');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7 = ClassRegistry::init('Schedules7');
        $Schedules168 = ClassRegistry::init('Schedules168');
        $Term = ClassRegistry::init('Term');

        $account_info = $Term->getAccountInfo();

        if ($account_info['AccountInfo']['return_class'] == 1) {
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'schedules1_blue_ocr_e290401.pdf');
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'e281231ocr_blue.pdf');
          } else {
            $template = $this->setTemplateAddPage($pdf, $font, 's280101OCRschedules1_blue.pdf');
          }
        } else {
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'schedules1_white_ocr_e290401.pdf');
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'e281231ocr_white.pdf');
          } else {
            $template = $this->setTemplateAddPage($pdf, $font, 's280101OCRschedules_white.pdf');
          }
        }

        //提出先
        $pdf->SetFont($font, null, 7.5, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(43, 18);
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(43, 18);
        } else {
          $pdf->SetXY(43, 18.2);
        }
        $pdf->MultiCell(26, 5, h($account_info['AccountInfo']['tax_office']), 0, 'R');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $data7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);
        $schedules1 = $Schedules1->findFor1($preSum, $data14['not_cost'], $data7['this_deduction_sum']);

        $term_id = CakeSession::read('Auth.User.term_id');

        if (isset($schedules1['user']) && !empty($schedules1['user'])) {
            $address = h($schedules1['user']['NameList']['prefecture'] . $schedules1['user']['NameList']['city']
                . $schedules1['user']['NameList']['address']);

            $x = array('x1' => null, 'x2' => 21.18);
            $y = array('y1' => 26, 'y2' => 24, 'y3' => 22.6);
            $align = array('align1' => null, 'align2' => 'L');
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putBaseStringWithLimit($pdf, $font, 7, $address, 3, 54, 2.5, 81, $x, $y, $align);
            } else {
              $y = array('y1' => 25.5, 'y2' => 23.5, 'y3' => 22.1);
              $this->_putBaseStringWithLimit($pdf, $font, 7, $address, 3, 54, 2.5, 81, $x, $y, $align);
            }

            $pdf->SetFont($font, null, 9, true);
            $phone_number = $schedules1['user']['NameList']['phone_number'];
            $margin_right = 0;
            foreach (explode('-', $phone_number) as $number) {
              if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
                $pdf->SetXY(52+$margin_right, 30.5);
              } else {
                $pdf->SetXY(52+$margin_right, 29);
              }
                $pdf->MultiCell(10, 5, $number, 0, 'C');
                $margin_right += 11.75;
            }

            $name_katakana = $schedules1['user']['NameList']['name_katakana'];
            $x = array('x1' => 12, 'x2' => null);
            $y = array('y1' => 34.4, 'y2' => null, 'y3' => null);
            $align = array('align1' => 'C', 'align2' => null);
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putBaseStringWithLimit($pdf, $font, 6.5, $name_katakana, 1, 58, 2.5, 87, $x, $y, $align);
            } else {
              $y = array('y1' => 33, 'y2' => null, 'y3' => null);
              $this->_putBaseStringWithLimit($pdf, $font, 6.5, $name_katakana, 1, 58, 2.5, 87, $x, $y, $align);
            }

            $pdf->SetFont($font, null, 9.2, true);
            $name_list = $schedules1['user']['NameList']['name'];
            $x = array('x1' => 21.3, 'x2' => 22);
            $y = array('y1' => 40.5, 'y2' => 38.5);
            $align = array('align1' => 'C', 'align2' => 'L');
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putBaseStringWithLimit($pdf, $font, 9.2, $name_list, 2, 40, 3.5, 68, $x, $y, $align);
            } else {
              $y = array('y1' => 38, 'y2' => 36);
              $this->_putBaseStringWithLimit($pdf, $font, 9.2, $name_list, 2, 40, 3.5, 68, $x, $y, $align);
            }

            $address_president = h($schedules1['president']['NameList']['prefecture'] . $schedules1['president']['NameList']['city']
                . $schedules1['president']['NameList']['address']);

            $x = array('x1' => null, 'x2' => 21.5);
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $y = array('y1' => 62, 'y2' => 60, 'y3' => 59);
            } else {
              $y = array('y1' => 63.5, 'y2' => 61.5, 'y3' => 60.5);
            }
            $align = array('align1' => null, 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 7, $address_president, 3, 54, 2.5, 81, $x, $y, $align);

            $user_business = $schedules1['user']['User']['business'];
            $x = array('x1' => 99.7, 'x2' => null);
            $y = array('y1' => 20.5, 'y2' => null, 'y3' => null);
            $align = array('align1' => 'C', 'align2' => null);
            $this->_putBaseStringWithLimit($pdf, $font, 6.4, $user_business, 1, 28, 2.5, 42, $x, $y, $align);
        }

        $capital = number_format($schedules1['capital']);
        if (strlen($capital) <= 13) {
            $pdf->SetXY(102, 25.5);
            $pdf->MultiCell(26, 5, $capital, 0, 'C');
        }

        //整理番号
        $pdf->SetFont($font, null, 9, true);
        $seiri_num = $schedules1['user']['User']['seiri_num'];
        $this->_putsCharLeftToRigth($pdf, 182.8, 21.6, $seiri_num, 5);

        $pdf->SetFont($font, null, 11.5, true);
        //普通法人に◯
        //$pdf->SetXY(125.8, 38);
        //$pdf->MultiCell(10, 5, '◯', 0, 'L');

        if($user['no_equity_flag'] == 1){
            //非同族会社に◯
            $pdf->SetXY(128.7, 32.1);
        } else if ($schedules1['judge'] == '同族会社') {
            //同族会社に◯
            $pdf->SetXY(117.7, 32.1);
        } else {
            //非同族会社に◯
            $pdf->SetXY(128.7, 32.1);
        }
        $pdf->MultiCell(10, 10, '◯', 0, 'L');

        //非営利型に◯
        if($user['no_business_flag'] == 1){
          $pdf->SetXY(128.9, 38);
          $pdf->MultiCell(10, 10, '◯', 0, 'L');
        }

        $pdf->SetFont($font, 'B', 9, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          //貸借対照表に◯
          $pdf->SetXY(106.5, 58);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //損益計算書に◯
          $pdf->SetXY(117.5, 58);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //株主資本等変動計算書に◯
          $pdf->SetXY(110, 59.5);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //勘定科目内訳書に◯
          $pdf->SetXY(107, 61.5);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //事業概況書に◯
          $pdf->SetXY(120, 61.5);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
        } else {
          //貸借対照表に◯
          $pdf->SetXY(106.5, 58.5);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //損益計算書に◯
          $pdf->SetXY(117.5, 58.5);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //株主資本等変動計算書に◯
          $pdf->SetXY(110, 60);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //勘定科目内訳書に◯
          $pdf->SetXY(107, 62);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
          //事業概況書に◯
          $pdf->SetXY(120, 62);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
        }

          $pdf->SetFont($font, null, 11.5, true);
        if ($schedules1['details_flag'] == 1) {
            //有に◯
            $pdf->SetXY(181, 72.5);
        } else {
            //無に◯
            $pdf->SetXY(188, 72.5);
        }
        $pdf->MultiCell(10, 5, '◯', 0, 'L');

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
        )));

        $y1 = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
        $m1 = date('n',strtotime($term['Term']['account_beggining'])) ;
        $d1 = date('j',strtotime($term['Term']['account_beggining'])) ;

        $this->_putsCharLeftToRigth($pdf, 30.5, 70.32, $y1, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 47.5, 70.32, $m1, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 65, 70.32, $d1, 5, 'C');

        $y2 = date('Y',strtotime($term['Term']['account_end'])) -1988;
        $m2 = date('n',strtotime($term['Term']['account_end'])) ;
        $d2 = date('j',strtotime($term['Term']['account_end'])) ;

        $this->_putsCharLeftToRigth($pdf, 30.5, 78.5, $y2, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 47.5, 78.5, $m2, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 65, 78.5, $d2, 5, 'C');

        $pdf->SetFont($font, 'B', 9.75, true);
        for ($i=0; $i < 4; $i++) {
            $pdf->SetXY(109.5, 71);
            $pdf->MultiCell(20, 5, '確定', 0, 'C');
        }

        for ($i=0; $i < 4; $i++) {
            $pdf->SetXY(109.5, 75.7);
            $pdf->MultiCell(20, 5, '確定', 0, 'C');
        }

        $pdf->SetFont($font, null, 10, true);

        //法人番号
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        } else {
          $company_number = CakeSession::read('Auth.User.company_number');
          $this->_putIntNumber($pdf, 76.5, 44.6, $company_number, 4.92, 'R', 0.9);
        }

        //line 1
        if (isset($schedules1['shotoku'])) {
            $shotoku = $schedules1['shotoku'];
            $this->_putIntNumber($pdf, 88.9, 92.2, $shotoku, 4.92, 'R', 0.9);
        }
        //line 2
        $houjinzei = $schedules1['houjinzei'];
        $this->_putIntNumber($pdf, 89, 98.35, $houjinzei, 4.92, 'R', 0.9);

        //line 3
        $houjinzeigaku_tokubetsukoujyo = $schedules1['houjinzeigaku_tokubetsukoujyo'];
        $this->_putIntNumber($pdf, 89, 107.5, $houjinzeigaku_tokubetsukoujyo, 4.92, 'R', 0.9);

        //line 4, 10, 32
        $sashihiki_houjinzei = $schedules1['sashihiki_houjinzei'];
        $this->_putIntNumber($pdf, 89, 113.8, $sashihiki_houjinzei, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 89, 156.8, $sashihiki_houjinzei, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 89, 200, $sashihiki_houjinzei, 4.92, 'R', 0.9);

        //line 12, 19
        $koujyozeigaku = $schedules1['koujyozeigaku'];
        $this->_putIntNumber($pdf, 89, 169.25, $koujyozeigaku, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 180, 110.75, $koujyozeigaku, 4.92, 'R', 0.9);

        //line 13
        $sashihiki_shotokunitaisuruhoujinzei = intval($schedules1['sashihiki_shotokunitaisuruhoujinzei']);
        if ($sashihiki_shotokunitaisuruhoujinzei >= 100) {
            $sashihiki_shotokunitaisuruhoujinzei = (int)($sashihiki_shotokunitaisuruhoujinzei/100);
            $this->_putIntNumber($pdf, 79, 175.3, $sashihiki_shotokunitaisuruhoujinzei, 4.92, 'R', 0.9);
        }

        //line 14
        $middle_tax = intval($schedules1['middle_tax']);
        if ($middle_tax >= 100) {
            $middle_tax = (int)($middle_tax/100);
            $this->_putIntNumber($pdf, 79, 181.6, $middle_tax, 4.92, 'R', 0.9);
        }

        //line 15
        $kakutei_houjinzei = intval($schedules1['kakutei_houjinzei']);
        if ($kakutei_houjinzei >= 100) {
            $this->_putIntNumber($pdf, 79, 189.2, (int)($kakutei_houjinzei/100), 4.92, 'R', 0.9);
        }

        //line 16, 18
        $shotokuzeigaku = $schedules1['shotokuzeigaku'];
        $this->_putIntNumber($pdf, 180, 92.2, $shotokuzeigaku, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 180, 104.4, $shotokuzeigaku, 4.92, 'R', 0.9);

        //line 20 24
        $koujyoshikirenai = $schedules1['koujyoshikirenai'];
        $this->_putIntNumber($pdf, 180, 116.8, $koujyoshikirenai, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 180, 141.5, $koujyoshikirenai, 4.92, 'R', 0.9);

        //line 25
        $chukankanpu = $schedules1['chukankanpu'];
        $this->_putIntNumber($pdf, 180, 147.5, $chukankanpu, 4.92, 'R', 0.9);

        //line 27
        $kanpu_sum = intval($schedules1['kanpu_sum']);
        if ($kanpu_sum > 0) {
            $this->_putIntNumber($pdf, 180, 162.9, $kanpu_sum, 4.92, 'R', 0.9);
        }

        //line 30
        $kessonkin_toukikoujyo = $schedules1['kessonkin_toukikoujyo'];
        $this->_putIntNumber($pdf, 180, 183, $kessonkin_toukikoujyo, 4.92, 'R', 0.9);

        //line 31
        $kurikoshikessonkin = $schedules1['kurikoshikessonkin'];
        $this->_putIntNumber($pdf, 180, 189.2, $kurikoshikessonkin, 4.92, 'R', 0.9);

        //line 34
        $sashihiki_houjinzei = intval($schedules1['sashihiki_houjinzei']);
        if ($sashihiki_houjinzei >= 1000) {
            $sashihiki_houjinzei = (int)($sashihiki_houjinzei/1000);
            $this->_putIntNumber($pdf, 74, 212.3, $sashihiki_houjinzei, 4.92, 'R', 0.9);
        }

        //line 35, 37
        $local_houjinzei = $schedules1['local_houjinzei'];
        $this->_putIntNumber($pdf, 89, 218.5, $local_houjinzei, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 89, 231, $local_houjinzei, 4.92, 'R', 0.9);

        //line 40
        $sashihiki_local_houjinzei = intval($schedules1['sashihiki_local_houjinzei']);
        if ($sashihiki_local_houjinzei >= 100) {
            $sashihiki_local_houjinzei = (int)($sashihiki_local_houjinzei/100);
            $this->_putIntNumber($pdf, 79, 249.3, $sashihiki_local_houjinzei, 4.92, 'R', 0.9);
        }

        //line 41
        $middle_local_houjinzei = intval($schedules1['middle_local_houjinzei']);
        if ($middle_local_houjinzei >= 100) {
            $middle_local_houjinzei = (int)($middle_local_houjinzei/100);
            $this->_putIntNumber($pdf, 79, 255.7, $middle_local_houjinzei, 4.92, 'R', 0.9);
        }

        //line 42
        $sashihiki_kakutei_local_houjinzei = $schedules1['sashihiki_kakutei_local_houjinzei'];
        if (!empty($sashihiki_kakutei_local_houjinzei) && $sashihiki_kakutei_local_houjinzei != 0) {
            $pdf->SetAutoPageBreak(false, 0);
            $sashihiki_kakutei_local_houjinzei = intval($sashihiki_kakutei_local_houjinzei);
            if ($sashihiki_kakutei_local_houjinzei >= 100) {
                $sashihiki_kakutei_local_houjinzei = (int)($sashihiki_kakutei_local_houjinzei/100);
                $this->_putIntNumber($pdf, 79, 263, $sashihiki_kakutei_local_houjinzei, 4.92, 'R', 0.9);
            }
        }

        //line 43
        $local_chukan_kanpu = $schedules1['local_chukan_kanpu'];
        $this->_putIntNumber($pdf, 180, 201.5, $local_chukan_kanpu, 4.92, 'R', 0.9);

        $haitou = $schedules1['haitou'];
        if (!empty($haitou)) {
            $this->_putIntNumber($pdf, 180, 232, $haitou, 4.92, 'R', 0.9);
        }

        $pdf->SetFont($font, null, 11, true);
        if (isset($schedules1['user']['confirm_date']) && !empty($schedules1['user']['confirm_date'])) {
            $y1 = date('Y',strtotime($schedules1['user']['confirm_date'])) -1988;
            $m1 = date('n',strtotime($schedules1['user']['confirm_date'])) ;
            $d1 = date('j',strtotime($schedules1['user']['confirm_date'])) ;

            $this->_putsCharLeftToRigth($pdf, 163.5, 240, $y1, 4.9, 'C');
            $this->_putsCharLeftToRigth($pdf, 173.5, 240, $m1, 4.9, 'C');
            $this->_putsCharLeftToRigth($pdf, 183.5, 240, $d1, 4.9, 'C');
        }

        if (isset($schedules1['user']['kanpu_bank_name']) && !empty($schedules1['user']['kanpu_bank_name'])) {
            $x = array('x1' => 109.2, 'x2' => 110.5);
            $y = array('y1' => 249.2, 'y2' => 248.3, 'y3' => 246.8);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.2, $schedules1['user']['kanpu_bank_name'], 3, 14, 2.5, 21, $x, $y, $align);
        }

        if (isset($schedules1['user']['kanpu_branch_name']) && !empty($schedules1['user']['kanpu_branch_name'])) {
            $x = array('x1' => 136.3, 'x2' => 137);
            $y = array('y1' => 249.2, 'y2' => 248.3, 'y3' => 246.8);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.2, $schedules1['user']['kanpu_branch_name'], 3, 10, 2.5, 15, $x, $y, $align);
        }

        if (isset($schedules1['user']['kanpu_account_class']) && !empty($schedules1['user']['kanpu_account_class'])) {
            $x = array('x1' => 159, 'x2' => 159.2);
            $y = array('y1' => 249.2, 'y2' => 248.3, 'y3' => 246.8);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.2, $schedules1['user']['kanpu_account_class'], 3, 6, 2.5, 9, $x, $y, $align);
        }

        $pdf->SetFont($font, null, 9, true);
        if (isset($schedules1['user']['kanpu_number']) && !empty($schedules1['user']['kanpu_number'])) {
            $kanpu_number = $schedules1['user']['kanpu_number'];
            $pdf->SetAutoPageBreak(FALSE);
            $this->_putNumberTableItem($pdf, $kanpu_number, 107, 258, [3.8, 3.6]);
        }

        return $pdf;
    }

    /**
     * 普通法人等の申告書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules0101s($pdf, $font) {
        $user = CakeSession::read('Auth.User');

        $Schedules1 = ClassRegistry::init('Schedules1');

        $Schedules1 = ClassRegistry::init('Schedules1');
        $FixedAsset = ClassRegistry::init('FixedAsset');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7 = ClassRegistry::init('Schedules7');
        $Schedules168 = ClassRegistry::init('Schedules168');
        $Term = ClassRegistry::init('Term');

        //事業年度で様式選択
        $term_info = $Schedules1->getCurrentTerm();
        $target_day = '2016/01/01';
        $target_day29 = '2017/04/01';

        $account_info = $Term->getAccountInfo();

        if ($account_info['AccountInfo']['return_class'] == 1) {
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'schedules1_blue_e290401.pdf');
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules1_blue.pdf');
          } else {
            $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules1_blue.pdf');
          }
        } else {
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'schedules1_white_e290401.pdf');
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules1_white.pdf');
          } else {
            $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules1_white.pdf');
          }
        }

        //提出先
        $pdf->SetFont($font, null, 7.5, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(43, 18);
        } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(43, 18);
        } else {
          $pdf->SetXY(43, 18.2);
        }
        $pdf->MultiCell(26, 5, h($account_info['AccountInfo']['tax_office']), 0, 'R');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $data7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);
        $schedules1 = $Schedules1->findFor1($preSum, $data14['not_cost'], $data7['this_deduction_sum']);

        $term_id = CakeSession::read('Auth.User.term_id');

        if (isset($schedules1['user']) && !empty($schedules1['user'])) {
            $address = h($schedules1['user']['NameList']['prefecture'] . $schedules1['user']['NameList']['city']
                . $schedules1['user']['NameList']['address']);

            $x = array('x1' => null, 'x2' => 21.18);
            $y = array('y1' => 26, 'y2' => 24, 'y3' => 22.6);
            $align = array('align1' => null, 'align2' => 'L');
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putBaseStringWithLimit($pdf, $font, 7, $address, 3, 54, 2.5, 81, $x, $y, $align);
            } else {
              $y = array('y1' => 25.5, 'y2' => 23.5, 'y3' => 22.1);
              $this->_putBaseStringWithLimit($pdf, $font, 7, $address, 3, 54, 2.5, 81, $x, $y, $align);
            }

            $pdf->SetFont($font, null, 9, true);
            $phone_number = $schedules1['user']['NameList']['phone_number'];
            $margin_right = 0;
            foreach (explode('-', $phone_number) as $number) {
              if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(52+$margin_right, 30.5);
              } else {
                $pdf->SetXY(52+$margin_right, 29);
              }
                $pdf->MultiCell(10, 5, $number, 0, 'C');
                $margin_right += 11.75;
            }

            $name_katakana = $schedules1['user']['NameList']['name_katakana'];
            $x = array('x1' => 12, 'x2' => null);
            $y = array('y1' => 34.4, 'y2' => null, 'y3' => null);
            $align = array('align1' => 'C', 'align2' => null);
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putBaseStringWithLimit($pdf, $font, 6.5, $name_katakana, 1, 58, 2.5, 87, $x, $y, $align);
            } else {
              $y = array('y1' => 33, 'y2' => null, 'y3' => null);
              $this->_putBaseStringWithLimit($pdf, $font, 6.5, $name_katakana, 1, 58, 2.5, 87, $x, $y, $align);
            }

            $pdf->SetFont($font, null, 9.2, true);
            $name_list = $schedules1['user']['NameList']['name'];
            $x = array('x1' => 21.3, 'x2' => 22);
            $y = array('y1' => 40.5, 'y2' => 38.5);
            $align = array('align1' => 'C', 'align2' => 'L');
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putBaseStringWithLimit($pdf, $font, 9.2, $name_list, 2, 40, 3.5, 68, $x, $y, $align);
            } else {
              $y = array('y1' => 38, 'y2' => 36);
              $this->_putBaseStringWithLimit($pdf, $font, 9.2, $name_list, 2, 40, 3.5, 68, $x, $y, $align);
            }

            $address_president = h($schedules1['president']['NameList']['prefecture'] . $schedules1['president']['NameList']['city']
                . $schedules1['president']['NameList']['address']);

            $x = array('x1' => null, 'x2' => 21.5);
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $y = array('y1' => 62, 'y2' => 60, 'y3' => 59);
            } else {
              $y = array('y1' => 63.5, 'y2' => 61.5, 'y3' => 60.5);
            }
            $align = array('align1' => null, 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 7, $address_president, 3, 54, 2.5, 81, $x, $y, $align);

            $user_business = $schedules1['user']['User']['business'];
            $x = array('x1' => 99.7, 'x2' => null);
            $y = array('y1' => 20.5, 'y2' => null, 'y3' => null);
            $align = array('align1' => 'C', 'align2' => null);
            $this->_putBaseStringWithLimit($pdf, $font, 6.4, $user_business, 1, 28, 2.5, 42, $x, $y, $align);
        }

        $capital = number_format($schedules1['capital']);
        if (strlen($capital) <= 13) {
            $pdf->SetXY(102, 25.5);
            $pdf->MultiCell(26, 5, $capital, 0, 'C');
        }

        $pdf->SetFont($font, null, 11.5, true);
        //普通法人に◯
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(125.8, 38);
          $pdf->MultiCell(10, 5, '◯', 0, 'L');
        } else {

        }

        if($user['no_equity_flag'] == 1){
            //非同族会社に◯
            $pdf->SetXY(128.7, 32.1);
        } else if ($schedules1['judge'] == '同族会社') {
            //同族会社に◯
            $pdf->SetXY(117.7, 32.1);
        } else {
            //非同族会社に◯
            $pdf->SetXY(128.7, 32.1);
        }
        $pdf->MultiCell(10, 10, '◯', 0, 'L');

        //非営利型に◯
        if($user['no_business_flag'] == 1){
          $pdf->SetXY(128.9, 38);
          $pdf->MultiCell(10, 10, '◯', 0, 'L');
        }

        $pdf->SetFont($font, 'B', 9, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        //貸借対照表に◯
        $pdf->SetXY(106.5, 58);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //損益計算書に◯
        $pdf->SetXY(117.5, 58);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //株主資本等変動計算書に◯
        $pdf->SetXY(110, 59.5);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //勘定科目内訳書に◯
        $pdf->SetXY(107, 61.5);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //事業概況書に◯
        $pdf->SetXY(120, 61.5);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
      } else {
        //貸借対照表に◯
        $pdf->SetXY(106.5, 58.5);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //損益計算書に◯
        $pdf->SetXY(117.5, 58.5);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //株主資本等変動計算書に◯
        $pdf->SetXY(110, 60);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //勘定科目内訳書に◯
        $pdf->SetXY(107, 62);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
        //事業概況書に◯
        $pdf->SetXY(120, 62);
        $pdf->MultiCell(10, 5, '◯', 0, 'L');
      }

      //整理番号
      $pdf->SetFont($font, null, 9, true);
      $seiri_num = $schedules1['user']['User']['seiri_num'];
      $this->_putsCharLeftToRigth($pdf, 182.8, 21.6, $seiri_num, 5);

        // //貸借対照表に◯
        // $pdf->SetXY(106.5, 57.5);
        // $pdf->MultiCell(10, 5, '◯', 0, 'L');
        // //損益計算書に◯
        // $pdf->SetXY(117.5, 57.5);
        // $pdf->MultiCell(10, 5, '◯', 0, 'L');
        // //株主資本等変動計算書に◯
        // $pdf->SetXY(109, 59.5);
        // $pdf->MultiCell(10, 5, '◯', 0, 'L');
        // //勘定科目内訳書に◯
        // $pdf->SetXY(107, 61.5);
        // $pdf->MultiCell(10, 5, '◯', 0, 'L');
        // //事業概況書に◯
        // $pdf->SetXY(120, 61.5);
        // $pdf->MultiCell(10, 5, '◯', 0, 'L');

        $pdf->SetFont($font, null, 11.5, true);
        if ($schedules1['details_flag'] == 1) {
            //有に◯
            $pdf->SetXY(181, 72.5);
        } else {
            //無に◯
            $pdf->SetXY(188, 72.5);
        }
        $pdf->MultiCell(10, 5, '◯', 0, 'L');

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
        )));

        $y1 = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
    	$m1 = date('n',strtotime($term['Term']['account_beggining'])) ;
    	$d1 = date('j',strtotime($term['Term']['account_beggining'])) ;

        $this->_putsCharLeftToRigth($pdf, 30.5, 70.32, $y1, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 47.5, 70.32, $m1, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 65, 70.32, $d1, 5, 'C');

        $y2 = date('Y',strtotime($term['Term']['account_end'])) -1988;
    	$m2 = date('n',strtotime($term['Term']['account_end'])) ;
    	$d2 = date('j',strtotime($term['Term']['account_end'])) ;

        $this->_putsCharLeftToRigth($pdf, 30.5, 78.5, $y2, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 47.5, 78.5, $m2, 5, 'C');
        $this->_putsCharLeftToRigth($pdf, 65, 78.5, $d2, 5, 'C');

        $pdf->SetFont($font, 'B', 9.75, true);
        for ($i=0; $i < 4; $i++) {
            $pdf->SetXY(109.5, 71);
            $pdf->MultiCell(20, 5, '確定', 0, 'C');
        }

        for ($i=0; $i < 4; $i++) {
            $pdf->SetXY(109.5, 75.7);
            $pdf->MultiCell(20, 5, '確定', 0, 'C');
        }

        $pdf->SetFont($font, null, 10, true);
        //法人番号
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        } else {
          $company_number = CakeSession::read('Auth.User.company_number');
          $this->_putIntNumber($pdf, 76.5, 44.6, $company_number, 4.92, 'R', 0.9);
        }
        //line 1
        if (isset($schedules1['shotoku'])) {
            $shotoku = $schedules1['shotoku'];
            $this->_putIntNumber($pdf, 88.9, 92.2, $shotoku, 4.92, 'R', 0.9);
        }
        //line 2
        $houjinzei = $schedules1['houjinzei'];
        $this->_putIntNumber($pdf, 89, 98.35, $houjinzei, 4.92, 'R', 0.9);

        //line 3
        $houjinzeigaku_tokubetsukoujyo = $schedules1['houjinzeigaku_tokubetsukoujyo'];
        $this->_putIntNumber($pdf, 89, 107.5, $houjinzeigaku_tokubetsukoujyo, 4.92, 'R', 0.9);

        //line 4, 10, 32
        $sashihiki_houjinzei = $schedules1['sashihiki_houjinzei'];
        $this->_putIntNumber($pdf, 89, 113.8, $sashihiki_houjinzei, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 89, 156.8, $sashihiki_houjinzei, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 89, 200, $sashihiki_houjinzei, 4.92, 'R', 0.9);

        //line 12, 19
        $koujyozeigaku = $schedules1['koujyozeigaku'];
        $this->_putIntNumber($pdf, 89, 169.25, $koujyozeigaku, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 180, 110.75, $koujyozeigaku, 4.92, 'R', 0.9);

        //line 13
        $sashihiki_shotokunitaisuruhoujinzei = intval($schedules1['sashihiki_shotokunitaisuruhoujinzei']);
        if ($sashihiki_shotokunitaisuruhoujinzei >= 100) {
            $sashihiki_shotokunitaisuruhoujinzei = (int)($sashihiki_shotokunitaisuruhoujinzei/100);
            $this->_putIntNumber($pdf, 79, 175.3, $sashihiki_shotokunitaisuruhoujinzei, 4.92, 'R', 0.9);
        }

        //line 14
        $middle_tax = intval($schedules1['middle_tax']);
        if ($middle_tax >= 100) {
            $middle_tax = (int)($middle_tax/100);
            $this->_putIntNumber($pdf, 79, 181.6, $middle_tax, 4.92, 'R', 0.9);
        }

        //line 15
        $kakutei_houjinzei = intval($schedules1['kakutei_houjinzei']);
        if ($kakutei_houjinzei >= 100) {
            $this->_putIntNumber($pdf, 79, 189.2, (int)($kakutei_houjinzei/100), 4.92, 'R', 0.9);
        }

        //line 16, 18
        $shotokuzeigaku = $schedules1['shotokuzeigaku'];
        $this->_putIntNumber($pdf, 180, 92.2, $shotokuzeigaku, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 180, 104.4, $shotokuzeigaku, 4.92, 'R', 0.9);

        //line 20 24
        $koujyoshikirenai = $schedules1['koujyoshikirenai'];
        $this->_putIntNumber($pdf, 180, 116.8, $koujyoshikirenai, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 180, 141.5, $koujyoshikirenai, 4.92, 'R', 0.9);

        //line 25
        $chukankanpu = $schedules1['chukankanpu'];
        $this->_putIntNumber($pdf, 180, 147.5, $chukankanpu, 4.92, 'R', 0.9);

        //line 27
        $kanpu_sum = intval($schedules1['kanpu_sum']);
        if ($kanpu_sum > 0) {
            $this->_putIntNumber($pdf, 180, 162.9, $kanpu_sum, 4.92, 'R', 0.9);
        }

        //line 30
        $kessonkin_toukikoujyo = $schedules1['kessonkin_toukikoujyo'];
        $this->_putIntNumber($pdf, 180, 183, $kessonkin_toukikoujyo, 4.92, 'R', 0.9);

        //line 31
        $kurikoshikessonkin = $schedules1['kurikoshikessonkin'];
        $this->_putIntNumber($pdf, 180, 189.2, $kurikoshikessonkin, 4.92, 'R', 0.9);

        //line 34
        $sashihiki_houjinzei = intval($schedules1['sashihiki_houjinzei']);
        if ($sashihiki_houjinzei >= 1000) {
            $sashihiki_houjinzei = (int)($sashihiki_houjinzei/1000);
            $this->_putIntNumber($pdf, 74, 212.3, $sashihiki_houjinzei, 4.92, 'R', 0.9);
        }

        //line 35, 37
        $local_houjinzei = $schedules1['local_houjinzei'];
        $this->_putIntNumber($pdf, 89, 218.5, $local_houjinzei, 4.92, 'R', 0.9);
        $this->_putIntNumber($pdf, 89, 231, $local_houjinzei, 4.92, 'R', 0.9);

        //line 40
        $sashihiki_local_houjinzei = intval($schedules1['sashihiki_local_houjinzei']);
        if ($sashihiki_local_houjinzei >= 100) {
            $sashihiki_local_houjinzei = (int)($sashihiki_local_houjinzei/100);
            $this->_putIntNumber($pdf, 79, 249.3, $sashihiki_local_houjinzei, 4.92, 'R', 0.9);
        }

        //line 41
        $middle_local_houjinzei = intval($schedules1['middle_local_houjinzei']);
        if ($middle_local_houjinzei >= 100) {
            $middle_local_houjinzei = (int)($middle_local_houjinzei/100);
            $this->_putIntNumber($pdf, 79, 255.7, $middle_local_houjinzei, 4.92, 'R', 0.9);
        }

        //line 42
        $sashihiki_kakutei_local_houjinzei = $schedules1['sashihiki_kakutei_local_houjinzei'];
        if (!empty($sashihiki_kakutei_local_houjinzei) && $sashihiki_kakutei_local_houjinzei != 0) {
            $pdf->SetAutoPageBreak(false, 0);
            $sashihiki_kakutei_local_houjinzei = intval($sashihiki_kakutei_local_houjinzei);
            if ($sashihiki_kakutei_local_houjinzei >= 100) {
                $sashihiki_kakutei_local_houjinzei = (int)($sashihiki_kakutei_local_houjinzei/100);
                $this->_putIntNumber($pdf, 79, 263, $sashihiki_kakutei_local_houjinzei, 4.92, 'R', 0.9);
            }
        }

        //line 43
        $local_chukan_kanpu = $schedules1['local_chukan_kanpu'];
        $this->_putIntNumber($pdf, 180, 201.5, $local_chukan_kanpu, 4.92, 'R', 0.9);

        $haitou = $schedules1['haitou'];
        if (!empty($haitou)) {
            $this->_putIntNumber($pdf, 180, 232, $haitou, 4.92, 'R', 0.9);
        }

        $pdf->SetFont($font, null, 11, true);
        if (isset($schedules1['user']['confirm_date']) && !empty($schedules1['user']['confirm_date'])) {
            $y1 = date('Y',strtotime($schedules1['user']['confirm_date'])) -1988;
            $m1 = date('n',strtotime($schedules1['user']['confirm_date'])) ;
            $d1 = date('j',strtotime($schedules1['user']['confirm_date'])) ;

            $this->_putsCharLeftToRigth($pdf, 163.5, 240, $y1, 4.9, 'C');
            $this->_putsCharLeftToRigth($pdf, 173.5, 240, $m1, 4.9, 'C');
            $this->_putsCharLeftToRigth($pdf, 183.5, 240, $d1, 4.9, 'C');
        }

        if (isset($schedules1['user']['kanpu_bank_name']) && !empty($schedules1['user']['kanpu_bank_name'])) {
            $x = array('x1' => 109.2, 'x2' => 110.5);
            $y = array('y1' => 249.2, 'y2' => 248.3, 'y3' => 246.8);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.2, $schedules1['user']['kanpu_bank_name'], 3, 14, 2.5, 21, $x, $y, $align);
        }

        if (isset($schedules1['user']['kanpu_branch_name']) && !empty($schedules1['user']['kanpu_branch_name'])) {
            $x = array('x1' => 136.3, 'x2' => 137);
            $y = array('y1' => 249.2, 'y2' => 248.3, 'y3' => 246.8);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.2, $schedules1['user']['kanpu_branch_name'], 3, 10, 2.5, 15, $x, $y, $align);
        }

        if (isset($schedules1['user']['kanpu_account_class']) && !empty($schedules1['user']['kanpu_account_class'])) {
            $x = array('x1' => 159, 'x2' => 159.2);
            $y = array('y1' => 249.2, 'y2' => 248.3, 'y3' => 246.8);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.2, $schedules1['user']['kanpu_account_class'], 3, 6, 2.5, 9, $x, $y, $align);
        }

        $pdf->SetFont($font, null, 9, true);
        if (isset($schedules1['user']['kanpu_number']) && !empty($schedules1['user']['kanpu_number'])) {
            $kanpu_number = $schedules1['user']['kanpu_number'];
            $pdf->SetAutoPageBreak(FALSE);
            $this->_putNumberTableItem($pdf, $kanpu_number, 107, 258, [3.8, 3.6]);
        }

        return $pdf;
    }

    /**
     * 役員報酬手当等及び人件費の内訳書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_board_members($pdf, $font) {

        $template = $this->setTemplateAddPage($pdf, $font, 'board_members_etc.pdf');

        $BoardMember   = ClassRegistry::init('BoardMember');
        $Salary        = ClassRegistry::init('Salary');
        $board_members = $BoardMember->findForIndex();
        $salaries_data = $Salary->findForIndex();

        $point_start_y = 46;      // 出力開始位置起点(縦)
        $point_step    = 11;          // 次の出力
        $point_y       = 46;
        $height        = 35;  // 出力開始位置(縦)
        $fulltime_y    = 46.05;
        $fulltime_step = 10.95;
        $nl_position_x = 27.5;            // 科目名の表示位置
        $namelist_x    = 37.5;             // 名称の表示位置
        $relationship_x= 66.5;          // 所在地の表示位置
        $fulltime_x    = 78;         // 期末現在高の表示位置
        $salary_x      = 85;            // 摘要の表示位置
        $total_x       = $salary_x - 31.2;
        $salary_tbl_x  = 82;

        $utiwake_flg   = true;  // 人件費の内訳は一枚目のみ

        $price_margin = array(8.5, 5.2, 3.8);
        $record_count = 0;

        $salary_keys = array('total', 'for_employee', 'same_amount_salaries', 'determined_salaries', 'profit_related_pay', 'other_salaries', 'retirement_benefit');
        $salaries_summary = array_fill_keys($salary_keys, 0);

        foreach ($board_members as $data) {
            if ($record_count >= Configure::read('BOARD_MEMBERS_PDF_ROW')) {
                // ページ追加
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $record_count = 0;
                $point_y = $point_start_y;
                $fulltime_y = $point_start_y;
                $note_y = $point_start_y;
                $pay_y = $point_start_y;
            }

            $record_count++;
            $pdf->SetFont($font, null, 5.5, true);

            // NameList position
            $position = h($data['NameList']['position']);
            $height = ($record_count <= 1) ? $point_y + 5 : $point_y+0.5;
            $pdf->SetXY($nl_position_x, $height);
            $pdf->MultiCell(12, 5, $position, 0, 'L');

            // Duty
            if ($record_count > 1) {
                $duty = h($data['BoardMember']['duty']);
                $height = $point_y + 6;
                $pdf->SetXY($nl_position_x, $height);
                $pdf->MultiCell(30, 5, $duty, 0, 'L');
            }

            // NameList name
            $name_list = h($data['NameList']['name']);
            $height = $point_y+0.4;
            $pdf->SetXY($namelist_x, $height);
            $pdf->MultiCell(30, 5, $name_list, 0, 'C');

            // Full address
            $full_address = h($data['NameList']['prefecture']) . h($data['NameList']['city']) . h($data['NameList']['address']);
            $full_address = mb_strimwidth($full_address, 0, 80, '...', 'utf-8');
            $height = (mb_strwidth($full_address, 'utf8') <= 40) ? $fulltime_y + 6.1 : $fulltime_y + 4.9;
            $pdf->SetXY($namelist_x + 0.7, $height);
            $pdf->MultiCell(41, 5, $full_address, 0, 'L');

            // Relationship
            $relationship = h($data['NameList']['relationship']);
            $height = $point_y+0.4;
            $pdf->SetXY($relationship_x, $height);
            $pdf->MultiCell(30, 5, $relationship, 0, 'L');

            // Fulltime
            $pdf->SetFont($font, null, 13, true);
            $fulltime_mark = '◯';
            $fulltime = h($data['BoardMember']['full_time']);
            if (in_array($fulltime, array('常勤', '常'))) {
                $height = $fulltime_y-1.7;
                $pdf->SetXY($fulltime_x, $height);
                $pdf->MultiCell(10, 5, $fulltime_mark, 0, 'L');
            } else if (in_array($fulltime, array('非常勤', '非'))) {
                $height = $fulltime_y+4.8;
                $pdf->SetXY($fulltime_x, $height);
                $pdf->MultiCell(10, 5, $fulltime_mark, 0, 'L');
            }

            /* Salaries */
            $salaries = array();
            $pdf->SetFont($font, null, 5.5, true);

            // for employee
            $salaries['for_employee'] = $data['BoardMember']['for_employee'];
            $salaries_summary['for_employee'] += $salaries['for_employee'];
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x, $salaries['for_employee'], $price_margin);

            // same amount
            $salaries['same_amount_salaries'] = $data['BoardMember']['same_amount_salaries'];
            $salaries_summary['same_amount_salaries'] += $salaries['same_amount_salaries'];
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x+15.6, $salaries['same_amount_salaries'], $price_margin);

            // determined salaries
            $salaries['determined_salaries'] = $data['BoardMember']['determined_salaries'];
            $salaries_summary['determined_salaries'] += $salaries['determined_salaries'];
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x+31.2, $salaries['determined_salaries'], $price_margin);

            // profit related pay
            $salaries['profit_related_pay'] = $data['BoardMember']['profit_related_pay'];
            $salaries_summary['profit_related_pay'] += $salaries['profit_related_pay'];
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x+46.8, $salaries['profit_related_pay'], $price_margin);

            // other salaries
            $salaries['other_salaries'] = $data['BoardMember']['other_salaries'];
            $salaries_summary['other_salaries'] += $salaries['other_salaries'];
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x+62.4, $salaries['other_salaries'], $price_margin);

            // retirement benefit
            $salaries['retirement_benefit'] = $data['BoardMember']['retirement_benefit'];
            $salaries_summary['retirement_benefit'] += $salaries['retirement_benefit'];
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x+78, $salaries['retirement_benefit'], $price_margin);

            // total salaries
            $total_salaries = array_sum($salaries);
            $salaries_summary['total'] += $total_salaries;
            $height = $point_y + 7;
            $this->putPricePdf($pdf, $height, $salary_x-15.6, $total_salaries, $price_margin);

            if ($record_count < Configure::read('BOARD_MEMBERS_PDF_ROW')) {
                $point_y += $point_step;
                $fulltime_y += $fulltime_step;
            } else {
                if ($utiwake_flg) {
                  // Salary table
                  $pdf->SetFont($font, null, 9, true);
                  $height = $point_start_y + ($point_step * Configure::read('BOARD_MEMBERS_PDF_ROW')) + 36;
                  $this->_putBoardMemberSalaryTable($pdf, $height, $salary_tbl_x, $salaries_data);

                  $utiwake_flg = false;
                }
            }
        }

        if ($utiwake_flg) {
          // Salary table
          $pdf->SetFont($font, null, 9, true);
          $height = $point_start_y + ($point_step * Configure::read('BOARD_MEMBERS_PDF_ROW')) + 36;
          $this->_putBoardMemberSalaryTable($pdf, $height, $salary_tbl_x, $salaries_data);

          $utiwake_flg = false;
        }

        // 計
        $pdf->SetFont($font, null, 5.5, true);
        $height = $point_start_y + ($point_step * Configure::read('BOARD_MEMBERS_PDF_ROW')) + 7;
        foreach ($salaries_summary as $summary) {
            $total_x += 15.6;
            $this->putPricePdf($pdf, $height, $total_x, $summary, $price_margin, false);
        }
        $salaries_summary = array_fill_keys($salary_keys, 0);

        return $pdf;
    }

    /**
     * Salary table 人件費などの内訳出力
     *
     * @param FPDI OBJ $pdf
     * @param int $height
     * @param int $salary_tbl_x
     * @param int $salary_data
     */
    function _putBoardMemberSalaryTable(&$pdf, $height, $salary_tbl_x, $salary_data)
    {
        $margin = array(8.5, 13, 17.5);
        // board member compensation
        $board_member_compensation = $salary_data['Salary']['board_member_compensation'];
        $this->putPricePdf($pdf, $height, $salary_tbl_x, $board_member_compensation, $margin, false);
        $board_member_compensation_family = $salary_data['Salary']['board_member_compensation_family'];
        $this->putPricePdf($pdf, $height, $salary_tbl_x + 54.7, $board_member_compensation_family, $margin, false);
        $salary = $salary_data['Salary']['salary'];
        $this->putPricePdf($pdf, $height + 10, $salary_tbl_x, $salary, $margin, false);
        $salary_family = $salary_data['Salary']['salary_family'];
        $this->putPricePdf($pdf, $height + 10, $salary_tbl_x + 54.7, $salary_family, $margin, false);
        $wage = $salary_data['Salary']['wage'];
        $this->putPricePdf($pdf, $height + 20, $salary_tbl_x, $wage, $margin, false);
        $wage_family = $salary_data['Salary']['wage_family'];
        $this->putPricePdf($pdf, $height + 20, $salary_tbl_x + 54.7, $wage_family, $margin, false);
        $total_left = $board_member_compensation + $salary + $wage;
        $this->putPricePdf($pdf, $height + 30, $salary_tbl_x, $total_left, $margin, false);
        $total_right = $board_member_compensation_family + $salary_family + $wage_family;
        $this->putPricePdf($pdf, $height + 30, $salary_tbl_x + 54.7, $total_right, $margin, false);
    }

    /**
     * 適用額明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_special_details($pdf, $font) {

      $Schedules1 = ClassRegistry::init('Schedules1');

      //事業年度で様式選択
      $term_info = $Schedules1->getCurrentTerm();
      $target_day = '2016/01/01';
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'before_s280101_tekiyougakumeisaisho.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101tekiyougaku.pdf');
      }

      $account_info = $Schedules1->getAccountInfo();

      //提出先
      $pdf->SetFont($font, null, 7.5, true);
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
       $pdf->SetXY(34, 25.1);
      } else {
        $pdf->SetXY(34, 25.4);
      }

      $pdf->MultiCell(26, 5, h($account_info['AccountInfo']['tax_office']), 0, 'R');



        $FixedAsset = ClassRegistry::init('FixedAsset');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7 = ClassRegistry::init('Schedules7');
        $Schedules168 = ClassRegistry::init('Schedules168');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $data7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);

        $schedules1 = $Schedules1->findForDetails($preSum, $data14['not_cost'], $data7['this_deduction_sum']);



        if (isset($schedules1['user'])) {
            $pdf->SetFont($font, null, 10, true);
            $y1 = date('Y',strtotime($schedules1['user']['Term']['account_beggining'])) -1988;
            $m1 = date('n',strtotime($schedules1['user']['Term']['account_beggining'])) ;
            $d1 = date('j',strtotime($schedules1['user']['Term']['account_beggining'])) ;

            $this->_putsCharLeftToRigth($pdf, 86.8, 17.2, $y1, 5, 'C');
            $this->_putsCharLeftToRigth($pdf, 103.8, 17.2, $m1, 5, 'C');
            $this->_putsCharLeftToRigth($pdf, 121.3, 17.2, $d1, 5, 'C');

            $y2 = date('Y',strtotime($schedules1['user']['Term']['account_end'])) -1988;
            $m2 = date('n',strtotime($schedules1['user']['Term']['account_end'])) ;
            $d2 = date('j',strtotime($schedules1['user']['Term']['account_end'])) ;

            $this->_putsCharLeftToRigth($pdf, 86.8, 28.5, $y2, 5, 'C');
            $this->_putsCharLeftToRigth($pdf, 103.8, 28.5, $m2, 5, 'C');
            $this->_putsCharLeftToRigth($pdf, 121.3, 28.5, $d2, 5, 'C');

            $address = h($schedules1['user']['NameList']['prefecture'] . $schedules1['user']['NameList']['city']
                . $schedules1['user']['NameList']['address']);
            $x = array('x1' => null, 'x2' => 35.25);
            $y = array('y1' => 41.5, 'y2' => 40, 'y3' => null);
            $align = array('align1' => null, 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 7, $address, 2, 58, 2.8, 76, $x, $y, $align);

            $pdf->SetFont($font, null, 10, true);
            $phone_number = $schedules1['user']['NameList']['phone_number'];
            $margin_right = 0;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              foreach (explode('-', $phone_number) as $number) {
                  $pdf->SetXY(70+$margin_right, 46);
                  $pdf->MultiCell(10, 5, $number, 0, 'L');
                  $margin_right += 11.75;
              }
            } else {
              foreach (explode('-', $phone_number) as $number) {
                  $pdf->SetXY(69+$margin_right, 46);
                  $pdf->MultiCell(10, 5, $number, 0, 'L');
                  $margin_right += 11.75;
              }
            }

            $name_katakana = $schedules1['user']['NameList']['name_katakana'];
            $x = array('x1' => 18, 'x2' => null);
            $y = array('y1' => 51.3, 'y2' => null, 'y3' => null);
            $align = array('align1' => 'C', 'align2' => null);
            $this->_putBaseStringWithLimit($pdf, $font, 7, $name_katakana, 1, 58, 2.5, 108, $x, $y, $align);

            $name_list = $schedules1['user']['NameList']['name'];
            $x = array('x1' => 30.8, 'x2' => 35.8);
            $y = array('y1' => 59, 'y2' => 57);
            $align = array('align1' => 'C', 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 9, $name_list, 2, 44, 3.5, 82, $x, $y, $align);

            $pdf->SetFont($font, null, 9, true);
            $seiri_num = $schedules1['user']['User']['seiri_num'];
            $this->_putsCharLeftToRigth($pdf, 159, 40.5, $seiri_num, 5);

            $user_business = $schedules1['user']['User']['business'];
            $x = array('x1' => null, 'x2' => 126);
            $y = array('y1' => 60.8, 'y2' => 59.5, 'y3' => null);
            $align = array('align1' => null, 'align2' => 'L');
            $this->_putBaseStringWithLimit($pdf, $font, 6.25, $user_business, 2, 28, 2.5, 47, $x, $y, $align);

            $pdf->SetFont($font, null, 9, true);
            $business_num = $schedules1['user']['User']['business_num'];
            $this->_putsCharLeftToRigth($pdf, 171.2, 60, $business_num, 5);

        }
        $this->_putsCharLeftToRigth($pdf, 129.5, 50, 1, 5);
        $this->_putsCharLeftToRigth($pdf, 161.5, 50, 1, 5);

        $pdf->SetFont($font, null, 9, true);
        $capital = $schedules1['capital'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putIntNumber($pdf, 98, 70.2, $capital, 4.95, 'C', 0);
        } else {
          $this->_putIntNumber($pdf, 98, 76.5, $capital, 4.95, 'C', 0);
        }

        //法人番号
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        } else {
          $pdf->SetFont($font, null, 9, true);
          $company_number = ($schedules1['user']['User']['company_number']);
          $this->_putIntNumber($pdf, 98, 67.2, $company_number, 4.95, 'C', 0);
        }

        if (isset($schedules1['shotoku']) && !empty($schedules1['shotoku'])){
            $shotoku = $schedules1['shotoku'];
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $this->_putIntNumber($pdf, 98, 79.4, $shotoku, 4.95, 'C', 0);
            } else {
              $this->_putIntNumber($pdf, 98, 85.8, $shotoku, 4.95, 'C', 0);
            }
        }

        $step = 0;
        $step1 = 0;
        foreach ($schedules1['details'] as $detail) {
            $pdf->SetXY(21.5, 112.5+$step);
            $pdf->MultiCell(20, 5, $detail['jyo'], 0, 'C');

            $pdf->SetXY(40, 112.5+$step);
            $pdf->MultiCell(20, 5, $detail['jyo2'], 0, 'L');

            $pdf->SetXY(56, 112.5+$step);
            $pdf->MultiCell(10, 5, $detail['ko'], 0, 'C');

            $pdf->SetXY(76, 112.5+$step);
            $pdf->MultiCell(10, 5, $detail['go'], 0, 'C');

            $this->_putIntNumber($pdf, 109.6, 111+$step1, $detail['class'], 4.93, 'R', 0.9);
            $this->_putIntNumber($pdf, 171.1, 111+$step1, $detail['sum'], 4.93, 'R', 0.9);

            $step +=7.8;
            $step1 +=7.6;
        }
        return $pdf;
    }

    /**
     * 普通法人等の申告書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */

    function export_schedules0101js($pdf, $font) {

      $Schedules1 = ClassRegistry::init('Schedules1');

      //事業年度で様式選択
      $term_info = $Schedules1->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules1_next_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules1_next.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules1_next.pdf');
      }

        $FixedAsset = ClassRegistry::init('FixedAsset');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7 = ClassRegistry::init('Schedules7');
        $Schedules168 = ClassRegistry::init('Schedules168');
        $Term = ClassRegistry::init('Term');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $data7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);

        $schedules1 = $Schedules1->findFor1Next($preSum, $data14['not_cost'], $data7['this_deduction_sum']);

        $term_id = CakeSession::read('Auth.User.term_id');

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
        )));

        $y1 = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
    	$m1 = date('n',strtotime($term['Term']['account_beggining'])) ;
    	$d1 = date('j',strtotime($term['Term']['account_beggining'])) ;

    	$pdf->SetFont($font, null, 10, true);
    	$pdf->SetXY(83.5, 14);
    	$pdf->MultiCell(38, 5, $y1, 0, 'R');
    	$pdf->SetXY(91, 14);
    	$pdf->MultiCell(38, 5, $m1, 0, 'R');
    	$pdf->SetXY(99, 14);
    	$pdf->MultiCell(38, 5, $d1, 0, 'R');

    	$y2 = date('Y',strtotime($term['Term']['account_end'])) -1988;
    	$m2 = date('n',strtotime($term['Term']['account_end'])) ;
    	$d2 = date('j',strtotime($term['Term']['account_end'])) ;

    	$pdf->SetXY(83.5, 18.5);
    	$pdf->MultiCell(38, 5, $y2, 0, 'R');
    	$pdf->SetXY(91, 18.5);
    	$pdf->MultiCell(38, 5, $m2, 0, 'R');
    	$pdf->SetXY(99, 18.5);
    	$pdf->MultiCell(39, 5, $d2, 0, 'R');



        // 名称
        $pdf->SetFont($font, 'B', 8.1, true);
        //$user_name = $this->roundLineStrByWidth($user['name'], 28);
        $user_name = substr($user['name'],0, 84);
        $height = (mb_strwidth($user_name, 'utf8') <= 28) ? 17 : 15;
        $pdf->SetXY(153, $height);
        $pdf->MultiCell(43, 5, $user_name, 0, 'L');

        //月数
        $pdf->SetFont($font, 'B', 8.1, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(51.5, 39.7);
        } else {
          $pdf->SetXY(51.3, 39.7);
        }
        $pdf->MultiCell(43, 5, $schedules1['period_month'], 0, 'C');


        $step = 0.1;
        $pdf->SetFont($font, 'B', 8, true);
        $pdf->SetTextColor(6, 6, 6);
        $under800 = $schedules1['under800'];
        if (!empty($under800) && strlen(number_format($under800 / 1000)) <= 6) {
            $under800 = $under800 / 1000;
            $pdf->SetXY(81.6, 43.5 + $step);
            $pdf->MultiCell(20, 5, ',', 0, 'R');
            $this->_putBaseNumber($pdf, $font, $under800, 80, 43.39 + $step, 20, 5, 'R', 8.4);
        }

        $tax15 = $schedules1['tax15'];
      //  if (!empty($tax15) && strlen(number_format($tax15)) <= 13) {
            $this->_putBaseNumber($pdf, $font, $tax15, 175, 43.39 + $step, 20, 5, 'R', 8.4);
      //  }

        $step += 16;
        $over800 = $schedules1['over800'];
        if (!empty($over800) && strlen(number_format($over800 / 1000)) <= 10) {
            $over800 = $over800 / 1000;
            $pdf->SetXY(81.6, 43.5 + $step);
            $pdf->MultiCell(20, 5, ',', 0, 'R');
            $this->_putBaseNumber($pdf, $font, $over800, 80, 43.39 + $step, 20, 5, 'R', 8.4);
        }

        $taxOver15 = $schedules1['taxOver15'];
      //  if (!empty($taxOver15) && strlen(number_format($taxOver15)) <= 13) {
            $this->_putBaseNumber($pdf, $font, $taxOver15, 175, 43.39 + $step, 20, 5, 'R', 8.4);
    //    }

        $step += 16;
        $shotoku = $schedules1['shotoku'];
        if (!empty($shotoku) && strlen(number_format($shotoku / 1000)) <= 10) {
            $shotoku = $shotoku / 1000;
            $pdf->SetXY(81.6, 43.5 + $step);
            $pdf->MultiCell(20, 5, ',', 0, 'R');
            $this->_putBaseNumber($pdf, $font, $shotoku, 80, 43.41 + $step, 20, 5, 'R', 8.4);
        }

        $tax_sum = $schedules1['tax_sum'];
      //  if (!empty($tax_sum) && strlen(number_format($tax_sum)) <= 13) {
            $this->_putBaseNumber($pdf, $font, $tax_sum, 175, 43.39 + $step, 20, 5, 'R', 8.4);
    //    }

        $tax_to_shotoku = $schedules1['tax_to_shotoku'];
        if (!empty($tax_to_shotoku) && strlen(number_format($tax_to_shotoku / 1000)) <= 10) {
            $tax_to_shotoku = $tax_to_shotoku / 1000;
            $pdf->SetXY(81.6, 119.5);
            $pdf->MultiCell(20, 5, ',', 0, 'R');
            $this->_putBaseNumber($pdf, $font, $tax_to_shotoku, 80, 119.39, 20, 5, 'R', 8.4);
        }

        $local_houjinzei = $schedules1['local_houjinzei'];
      //  if (!empty($local_houjinzei) && strlen(number_format($local_houjinzei)) <= 13) {
            $this->_putBaseNumber($pdf, $font, $local_houjinzei, 175, 119.39, 20, 5, 'R', 8.4);
    //    }
        //課税留保金額に対する法人税額
        $kazeiryuhokin = '0';
        $this->_putBaseNumber($pdf, $font, $kazeiryuhokin, 175, 135.39, 20, 5, 'R', 8.4);

        return $pdf;
    }

    /**
     * 寄附金の損金算入に関する明細書
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules14s($pdf, $font) {
      $pdf->SetAutoPageBreak(false);

      $Schedules14 = ClassRegistry::init('Schedules14');

      //事業年度で様式選択
      $term_info = $Schedules14->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules14_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules14.pdf');
      }

        $Schedules4  = ClassRegistry::init('Schedules4');
        $model       = ClassRegistry::init('FixedAsset');
        $Schedules168       = ClassRegistry::init('Schedules168');
        $Term        = ClassRegistry::init('Term');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $model->depreciationTotalCal();
        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'],$data16['minus']);
        $data        = $Schedules14->findFor14($preSum);

        $point_start_y     = 37;      // 出力開始位置起点(縦)
        $point_step        = 15.5;          // 次の出力
        $point_y = $height = 18;  // 出力開始位置(縦)

        //事業年度の月数を取得
        $pdf->SetFont($font, null, 5, true);
        $months = $model->getCurrentYear();
        $height = $point_y + 2;
        $pdf->SetXY(66.4, 147.5);
        $pdf->MultiCell(38, 5, $months, 0, 'L');

	      $term_id = CakeSession::read('Auth.User.term_id');

        $pdf->SetFont($font, null, 12, true);

        $term = $Term->find('first', array(
            'conditions' => array('Term.id' => $term_id)
            ));

        //Term.account_begginning
        $pdf->SetFont($font, null, 10, true);
        $account_beggining = $term['Term']['account_beggining'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $date_margin       = array(-4.8, -3.6, -2.0);
        } else {
          $date_margin       = array(-6.8, -6, -5.2);
        }
        $this->putHeiseiDate($pdf, 14.5, 125, $account_beggining, $date_margin, true);

        //Term.account_end
        $account_end = $term['Term']['account_end'];
        $this->putHeiseiDate($pdf, 19.2, 125, $account_end, $date_margin, true);

        // 法人名
        $pdf->SetFont($font, null, 7.6, true);
        $user_name = CakeSession::read('Auth.User.name');
        $user_name = $this->roundLineStrByWidth($user_name, 28);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height    = (mb_strwidth($user_name, 'utf8') <= 28) ? 17.8 : 16.2;
          $pdf->SetXY(159.5, $height);
        } else {
          $height    = (mb_strwidth($user_name, 'utf8') <= 28) ? 16.6 : 15;
          $pdf->SetXY(155.9, $height);
        }
        $align     = (mb_strwidth($user_name, 'utf8') <= 28) ? 'C' : 'L';
        $pdf->MultiCell(40, 5, $user_name, 0, $align);

        $x = 80;
        $y_step_row = 7.7;
        $pdf->SetFont($font, null, 8, true);

        //other donation 1 2 3 4 5 6
        $other_donation = $data['data3'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $x = 80;
          $height1 = 34.5;
          $height2 = 40.5;
          $height3 = 46;
          $height4 = 51.8;
          $height5 = 58;
          $height6 = 63.4;
        } else {
          $adjuastY = 3;
          $x = 80 -2;
          $height1 = 31.5;
          $height2 = 37;
          $height3 = 43;
          $height4 = 51.8 - 2.3;
          $height5 = 55.5;
          $height6 = 63.4 - 1.5;
        }
        $this->_putNumberItem($pdf, $data['data1'], $x, $height1);
        $this->_putNumberItem($pdf, $data['data2'], $x, $height2);
        $this->_putNumberItem($pdf, $data['data3'], $x, $height3);
        $this->_putNumberItem($pdf, $data['data4'], $x, $height4);
        $this->_putNumberItem($pdf, $data['Schedules14']['family_company_donations'], $x, $height5);
        $this->_putNumberItem($pdf, $data['data6'], $x, $height6);

        //presum row 7
        $preSum = $data['data7'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height = 70;
        } else {
          $height = 68.6;
        }
        $this->_putNumberItem($pdf, $preSum, $x, $height);

        //prepay row 8
        $prePay = $data['data8'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += $y_step_row;
        } else {
          $height += $y_step_row -0.1;
        }
        $this->_putNumberItem($pdf, $prePay, $x, $height);

        //multiply2.5 row 9
        $multiply25 = $data['multiply2.5'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height     = 85;
        } else {
          $height     = 85 - 2;
        }
        $this->_putNumberItem($pdf, $multiply25, $x, $height);

        //capital row 10
        $capital = $data['capital'];
        $height += $y_step_row;
        $this->_putNumberItem($pdf, $capital, $x, $height);

        //month row 11
        $pdf->SetFont($font, null, 6, true);
        $month  = $data['month'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 8.4;
          $pdf->SetXY($x - 40.3, $height + 0.5);
        } else {
          $height += 7.4;
          $pdf->SetXY($x - 42.9, $height -0.8);
        }
        $pdf->MultiCell(38, 5, $month, 0, 'C');

        //capital_detail row 12
        $pdf->SetFont($font, null, 8, true);
        $capital_detail = $data['capital_detail'];
        $this->_putNumberItem($pdf, $capital_detail, $x, $height - 0.2);

        //multiply0.25 row 13
        $multiply025 = $data['multiply0.25'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 8.1;
        } else {
          $height += 7.5;
        }
        $this->_putNumberItem($pdf, $multiply025, $x, $height);

        //limit_cost row 20
        $limit_cost = $data['limit_cost'];
        $height += 8.1;
        $this->_putNumberItem($pdf, $limit_cost, $x, $height);

        //row 14
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 8.1;
        } else {
          $height += 10;
        }
        $this->_putNumberItem($pdf, $data['data14'], $x, $height);

        //row 15
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 8.1;
        } else {
          $height += 10;
        }
        $this->_putNumberItem($pdf, $data['data15'], $x, $height);

        //row 16
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 9;
        } else {
          $height += 9.5;
        }
        $this->_putNumberItem($pdf, $data['data16'], $x, $height);

        //row 17
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 8.1;
        } else {
          $height += 7;
        }
        $this->_putNumberItem($pdf, $data['data17'], $x, $height);

        //row 18
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 6.5;
        } else {
          $height += 6.1;
        }
        $this->_putNumberItem($pdf, $data['data1'], $x, $height);

        //row 19
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 6.5;
        } else {
          $height += 6.1;
        }
        $this->_putNumberItem($pdf, $data['Schedules14']['oversea_donations'], $x, $height);

        //other_donation row 20
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height     = 170;
        } else {
          $height     = 168.5;
        }
        $this->_putNumberItem($pdf, $data['data20'], $x, $height);

        //row 22
        $height = 175;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 7.4;
        } else {
          $height += 6.1;
        }
        $this->_putNumberItem($pdf, $data['Schedules14']['oversea_donations'], $x, $height);

        //row 23
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height += 6;
        } else {
          $height += 6.1;
        }
        $this->_putNumberItem($pdf, $data['Schedules14']['family_company_donations'], $x, $height);

        // row21
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height1 = 175.8;
        } else {
          $height1 = 174.8;
        }
        $this->_putNumberItem($pdf, $data['data21'], $x, $height1,'R',null,true);
        //not_cost row 24
        $not_cost = $data['not_cost'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height2 = 194;
        } else {
          $height2 = 193.2;
        }
        if(!$not_cost){
          $not_cost = 0;
        }
        $this->_putNumberItem($pdf, $not_cost, $x, $height2,'R',null,true);

        //指定寄附金等に関する明細書部分
        //databaseから取得する
        $SpecifyDonation = ClassRegistry::init('SpecifyDonation');
        $datas2 = $SpecifyDonation->findSpecifyDonations();

        //特定公益増進法人等に呈する寄附金の明細部分
        $PublicDonation = ClassRegistry::init('PublicDonation');
        $datas3 = $PublicDonation->findPublicDonationsPlus();

        //特定公益信託部分
        //databaseから取得する
        $OtherDonation = ClassRegistry::init('OtherDonation');
        $datas4 = $OtherDonation->findOtherDonationsPlus();

        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $sub_sum = 0;
          $adjust = 1;

          for($a_key = 0;true;$a_key++){
            if (empty($datas2[$a_key]) && empty($datas3[$a_key]) && empty($datas4[$a_key])) {
              //全ての明細が終わったら終了
              break;
            }

            $line = $a_key % 3;
            //------------------------------------------------------data2
            if (!empty($datas2[$a_key])) {
              // 日
              $y = date('Y',strtotime($datas2[$a_key]['SpecifyDonation']['date'])) -1988;
              $m = date('n',strtotime($datas2[$a_key]['SpecifyDonation']['date'])) ;
              $d = date('j',strtotime($datas2[$a_key]['SpecifyDonation']['date'])) ;
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(32, 210.5 + $line * 5.4);
              $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 1, 'C');

              // 寄付先
              $text = substr($datas3[$a_key]['NameList']['name'],0,36);
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(57 + $adjust , 210.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 告示番号
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(92, 210.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, $datas2[$a_key]['SpecifyDonation']['public_number'], 0, 'C');

              // 使途
              $text = substr($datas3[$a_key]['SpecifyDonation']['purpose'],0,36);
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(127 + $adjust, 210.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 金額
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(157, 210.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, number_format($datas2[$a_key]['SpecifyDonation']['sum']), 0, 'R');

              if ($line == 0) {
                // 寄付金額41
                $sub_sum = 0;
                for ($i = 0; $i < 3; $i++) {
                  if (empty($datas2[$a_key + $i]['SpecifyDonation']['sum'])) break;
                  $sub_sum += $datas2[$a_key + $i]['SpecifyDonation']['sum'];
                }
                $pdf->SetFont($font, null, 8, true);
                $pdf->SetXY(157, 226.5);
                $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
              }
            }

            //------------------------------------------------------data3
            if (!empty($datas3[$a_key])) {
              // 日
              $y = date('Y',strtotime($datas3[$a_key]['PublicDonation']['date'])) -1988;
              $m = date('n',strtotime($datas3[$a_key]['PublicDonation']['date'])) ;
              $d = date('j',strtotime($datas3[$a_key]['PublicDonation']['date'])) ;
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(32, 245 + $line * 5.4);
              $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

              // 寄付先
              $text = substr($datas3[$a_key]['NameList']['name'],0,36);
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(57 + $adjust, 245 + $line * 5.4);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 所在地
              $text = $datas3[$a_key]['NameList']['prefecture'].
                      $datas3[$a_key]['NameList']['city'].
                      $datas3[$a_key]['NameList']['address'];
              $text = substr($text,0,36);
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(92 + $adjust, 245 + $line * 5.4);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 使途
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(127 + $adjust, 245 + $line * 5.4);
              $text = substr($datas3[$a_key]['PublicDonation']['purpose'],0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 金額
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(157, 245 + $line * 5.4);
              $pdf->MultiCell(37, 5, number_format($datas3[$a_key]['PublicDonation']['sum']), 0, 'R');

              if ($line == 0) {
                // 寄付金額41
                $sub_sum = 0;
                for ($i = 0; $i < 3; $i++) {
                  if (empty($datas3[$a_key + $i]['PublicDonation']['sum'])) break;
                  $sub_sum += $datas3[$a_key + $i]['PublicDonation']['sum'];
                }
                $pdf->SetFont($font, null, 8, true);
                $pdf->SetXY(157, 261);
                $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
              }
            }

            //------------------------------------------------------data4
            if (!empty($datas4[$a_key])) {
              // 日
              $y = date('Y',strtotime($datas4[$a_key]['OtherDonation']['date'])) -1988;
              $m = date('n',strtotime($datas4[$a_key]['OtherDonation']['date'])) ;
              $d = date('j',strtotime($datas4[$a_key]['OtherDonation']['date'])) ;
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(32, 276.5 + $line * 5.4);
              $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

              // 寄付先
              $text = substr($datas4[$a_key]['NameList']['name'],0,36);
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(57 + $adjust, 276.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 所在地
              $text = $datas4[$a_key]['NameList']['prefecture'].
                      $datas4[$a_key]['NameList']['city'].
                      $datas4[$a_key]['NameList']['address'];
              $text = substr($text,0,36);
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(92 + $adjust, 276.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 使途
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(125 + $adjust, 276.5 + $line * 5.4);
              $text = substr($datas4[$a_key]['OtherDonation']['purpose'],0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 金額
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(157, 276.5 + $line * 5.4);
              $pdf->MultiCell(37, 5, number_format($datas4[$a_key]['OtherDonation']['sum']), 0, 'R');
            }

            // 次ページ
            if ($line == 2 and (!empty($datas2[(int)$a_key + 1]) or !empty($datas3[(int)$a_key + 1]) or !empty($datas4[(int)$a_key + 1]))) {
              $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14.pdf');
            }
          }
        } else {
          $sub_sum = 0;

          for($a_key = 0;true;$a_key++){
            if (empty($datas2[$a_key]) && empty($datas3[$a_key]) && empty($datas4[$a_key])) {
              //全ての明細が終わったら終了
              break;
            }

            $line = $a_key % 3;
            //------------------------------------------------------data2
            if (!empty($datas2[$a_key])) {
              // 日
              $y = date('Y',strtotime($datas2[$a_key]['SpecifyDonation']['date'])) -1988;
              $m = date('n',strtotime($datas2[$a_key]['SpecifyDonation']['date'])) ;
              $d = date('j',strtotime($datas2[$a_key]['SpecifyDonation']['date'])) ;
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(27, 210.5 + $line * 4.8);
              $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

              // 寄付先
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(52, 210.5 + $line * 4.8);
              $text = substr($datas2[$a_key]['NameList']['name'],0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 告示番号
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(88, 210.5 + $line * 4.8);
              $pdf->MultiCell(37, 5, $datas2[$a_key]['SpecifyDonation']['public_number'], 0, 'C');

              // 使途
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(124, 210.5 + $line * 4.8);
              $text = substr($datas2[$a_key]['SpecifyDonation']['purpose'],0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 金額
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(157, 210.5 + $line * 4.8);
              $pdf->MultiCell(37, 5, number_format($datas2[$a_key]['SpecifyDonation']['sum']), 0, 'R');

              if ($line == 0) {
                // 寄付金額41
                $sub_sum = 0;
                for ($i = 0; $i < 3; $i++) {
                  if (empty($datas2[$a_key + $i]['SpecifyDonation']['sum'])) break;
                  $sub_sum += $datas2[$a_key + $i]['SpecifyDonation']['sum'];
                }
                $pdf->SetFont($font, null, 8, true);
                $pdf->SetXY(157, 224.5);
                $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
              }
            }

            //------------------------------------------------------data3
            if (!empty($datas3[$a_key])) {
              // 日
              $y = date('Y',strtotime($datas3[$a_key]['PublicDonation']['date'])) -1988;
              $m = date('n',strtotime($datas3[$a_key]['PublicDonation']['date'])) ;
              $d = date('j',strtotime($datas3[$a_key]['PublicDonation']['date'])) ;
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(27, 241 + $line * 4.8);
              $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

              // 寄付先
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(52, 241 + $line * 4.8);
              $text = substr($datas3[$a_key]['NameList']['name'],0,36);
              $pdf->MultiCell(37, 5,$text , 0, 'C');

              // 所在地
              $text = $datas3[$a_key]['NameList']['prefecture'].
                      $datas3[$a_key]['NameList']['city'].
                      $datas3[$a_key]['NameList']['address'];
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(88, 241 + $line * 4.8);
              $text = substr($text,0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 使途
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(124, 241 + $line * 4.8);
              $text = substr($datas3[$a_key]['PublicDonation']['purpose'],0,36);
              $pdf->MultiCell(37, 5,$text , 0, 'C');

              // 金額
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(157, 241 + $line * 4.8);
              $pdf->MultiCell(37, 5, number_format($datas3[$a_key]['PublicDonation']['sum']), 0, 'R');

              if ($line == 0) {
                // 寄付金額41
                $sub_sum = 0;
                for ($i = 0; $i < 3; $i++) {
                  if (empty($datas3[$a_key + $i]['PublicDonation']['sum'])) break;
                  $sub_sum += $datas3[$a_key + $i]['PublicDonation']['sum'];
                }
                $pdf->SetFont($font, null, 8, true);
                $pdf->SetXY(157, 255.5);
                $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
              }
            }

            //------------------------------------------------------data4
            if (!empty($datas4[$a_key])) {
              // 日
              $y = date('Y',strtotime($datas4[$a_key]['OtherDonation']['date'])) -1988;
              $m = date('n',strtotime($datas4[$a_key]['OtherDonation']['date'])) ;
              $d = date('j',strtotime($datas4[$a_key]['OtherDonation']['date'])) ;
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(27, 270 + $line * 4.8);
              $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

              // 寄付先
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(52, 270 + $line * 4.8);
              $text = substr($datas4[$a_key]['NameList']['name'],0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 所在地
              $text = $datas4[$a_key]['NameList']['prefecture'].
                      $datas4[$a_key]['NameList']['city'].
                      $datas4[$a_key]['NameList']['address'];
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(88, 270 + $line * 4.8);
              $text = substr($text,0,36);
              $pdf->MultiCell(37, 5, $text, 0, 'C');

              // 使途
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(124, 270 + $line * 4.8);
              $text = substr($datas4[$a_key]['OtherDonation']['purpose'],0,36);
              $pdf->MultiCell(37, 5,$text , 0, 'C');

              // 金額
              $pdf->SetFont($font, null, 8, true);
              $pdf->SetXY(157, 270 + $line * 4.8);
              $pdf->MultiCell(37, 5, number_format($datas4[$a_key]['OtherDonation']['sum']), 0, 'R');
            }

            // 次ページ
            if ($line == 2 and (!empty($datas2[(int)$a_key + 1]) or !empty($datas3[(int)$a_key + 1]) or !empty($datas4[(int)$a_key + 1]))) {
              $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules14.pdf');
            }
          }
        }

        return $pdf;
    }

    /**
     * 寄付金の損金算入に関する明細書
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules14_public($pdf, $font) {
      $Schedules14 = ClassRegistry::init('Schedules14');
      $Schedules4 = ClassRegistry::init('Schedules4');
      $FixedAsset = ClassRegistry::init('FixedAsset');
      $Term = ClassRegistry::init('Term');

      //別表14の値取得
      //減価償却超過額・認容額を計算
      $data16 = $FixedAsset->depreciationTotalCal();

      //寄付金損金不算入計算のために仮計取得
      $preSum = $Schedules4->calPreSum($data16['plus'],$data16['minus']);

      $datas = $Schedules14->findForPublicExport($preSum);

      $user = CakeSession::read('Auth.User');
      $term_id = CakeSession::read('Auth.User.term_id');
      $term = $Term->find('first',array(
          'conditions'=>array('Term.id'=>$term_id,
      )));

      //事業年度で様式選択
      $term_info = $Schedules14->getCurrentTerm();
      $target_day = '2016/04/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules14_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules14.pdf');
      }

      //事業年度の月数を取得
    $pdf->SetFont($font, null, 5, true);
    $months = $Schedules14->getCurrentYear();
    $height = $point_y + 2;
    $pdf->SetXY(66.4, 147.5);
    $pdf->MultiCell(38, 5, $months, 0, 'L');

    $term_id = CakeSession::read('Auth.User.term_id');

    $pdf->SetFont($font, null, 12, true);

    $term = $Term->find('first', array(
        'conditions' => array('Term.id' => $term_id)
        ));

    //Term.account_begginning
    $pdf->SetFont($font, null, 10, true);
    $account_beggining = $term['Term']['account_beggining'];
    if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
      $date_margin       = array(-4.8, -3.6, -2.0);
    } else {
      $date_margin       = array(-6.8, -6, -5.2);
    }
    $this->putHeiseiDate($pdf, 14.5, 125, $account_beggining, $date_margin, true);

    //Term.account_end
    $account_end = $term['Term']['account_end'];
    $this->putHeiseiDate($pdf, 19.2, 125, $account_end, $date_margin, true);

    // 法人名
    $pdf->SetFont($font, null, 7.6, true);
    $user_name = CakeSession::read('Auth.User.name');
    $user_name = $this->roundLineStrByWidth($user_name, 28);
    if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
      $height    = (mb_strwidth($user_name, 'utf8') <= 28) ? 17.8 : 16.2;
      $pdf->SetXY(159.5, $height);
    } else {
      $height    = (mb_strwidth($user_name, 'utf8') <= 28) ? 16.6 : 15;
      $pdf->SetXY(155.9, $height);
    }
    $align     = (mb_strwidth($user_name, 'utf8') <= 28) ? 'C' : 'L';
    $pdf->MultiCell(40, 5, $user_name, 0, $align);

    if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
      $x = 177;
    } else {
      $x = 178;
    }
    $y_step_row = 7.7;
    $pdf->SetFont($font, null, 8, true);

    if(strtotime($target_day) > strtotime($term_info['Term']['account_end'])){

      // 26
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 48.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['considered_donation']), 0, 'R');

      // 27
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 60.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['other_donation']), 0, 'R');

      // 28
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 73.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data28']), 0, 'R');

      // 29
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 85.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data29']), 0, 'R');

      // 30
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 93);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data30']), 0, 'R');

      // 31
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 105);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data31']), 0, 'R');

      // 32
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 121);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data32']), 0, 'R');

      // 34
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 150);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data34']), 0, 'R');

      // 35
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 157);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['specified_donation']), 0, 'R');

      // 36
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 164);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['special_donation']), 0, 'R');

      // 37
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 170);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data37']), 0, 'R');

      // 38
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 176);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data38']), 0, 'R');

      // 39
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 183);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['special_donation']), 0, 'R');

      // 40
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY(178, 192);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data40']), 0, 'R');

      $sub_sum = 0;
      foreach ($datas['sub'] as $s_key => $sub) {
        $line = $s_key % 3;
        if($sub['NameList']['name']){
        // 日
        $y = date('Y',strtotime($sub['SpecifyDonation']['date'])) -1988;
        $m = date('n',strtotime($sub['SpecifyDonation']['date'])) ;
        $d = date('j',strtotime($sub['SpecifyDonation']['date'])) ;
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(32, 210.5 + $line * 5.5);
        $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

        // 寄付先
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(57, 210.5 + $line * 5.5);
        $pdf->MultiCell(37, 5, $sub['NameList']['name'], 0, 'C');

        // 告示番号
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(93, 210.5 + $line * 5.5);
        $pdf->MultiCell(37, 5, $sub['SpecifyDonation']['public_number'], 0, 'C');

        // 使途
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(129, 210.5 + $line * 5.5);
        $pdf->MultiCell(37, 5, $sub['SpecifyDonation']['purpose'], 0, 'C');

        // 金額
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(160, 210.5 + $line * 5.5);
        $pdf->MultiCell(37, 5, number_format($sub['SpecifyDonation']['sum']), 0, 'R');

        if ($line == 0) {
          // $y = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
          // $m = date('n',strtotime($term['Term']['account_beggining'])) ;
          // $d = date('j',strtotime($term['Term']['account_beggining'])) ;
          //
          // $pdf->SetFont($font, null, 11, true);
          // $name = $y;
          // $pdf->SetXY(89, 15.5);
          // $pdf->MultiCell(38, 5, $name, 0, 'R');
          // $name = $m;
          // $pdf->SetXY(98, 15.5);
          // $pdf->MultiCell(38, 5, $name, 0, 'R');
          // $name = $d;
          // $pdf->SetXY(106, 15.5);
          // $pdf->MultiCell(38, 5, $name, 0, 'R');
          //
          // $y = date('Y',strtotime($term['Term']['account_end'])) -1988;
          // $m = date('n',strtotime($term['Term']['account_end'])) ;
          // $d = date('j',strtotime($term['Term']['account_end'])) ;
          //
          // $pdf->SetFont($font, null, 11, true);
          // $name = $y;
          // $pdf->SetXY(89, 20);
          // $pdf->MultiCell(38, 5, $name, 0, 'R');
          // $name = $m;
          // $pdf->SetXY(98, 20);
          // $pdf->MultiCell(38, 5, $name, 0, 'R');
          // $name = $d;
          // $pdf->SetXY(106, 20);
          // $pdf->MultiCell(38, 5, $name, 0, 'R');
          //
          // // 名称
          // $pdf->SetFont($font, null, 9, true);
          // $user_name = substr($user['name'],0,84);
          // $height = (mb_strwidth($user_name, 'utf8') <= 23) ? 17.5 : 14.8;
          // $pdf->SetXY(160.2, $height);
          // $pdf->MultiCell(37, 5, $user_name, 0, 'L');

          // 寄付金額41
          $sub_sum = 0;
          for ($i = 0; $i < 3; $i++) {
            if (empty($datas['sub'][$s_key + $i]['SpecifyDonation']['sum'])) break;
            $sub_sum += $datas['sub'][$s_key + $i]['SpecifyDonation']['sum'];
          }
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(160, 226);
          $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
        }
      }

        // 次ページ
        if ($line == 2 and !empty($datas['sub'][(int)$s_key + 1])) {
          $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14.pdf');
        }
      }

    } else {

      // 26
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 37.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['considered_donation']), 0, 'R');

      // 27
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 43.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['other_donation']), 0, 'R');

      // 28
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x,51);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data28']), 0, 'R');

      // 29
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 61);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data29']), 0, 'R');

      // 30
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 70.7);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data30']), 0, 'R');

      // 31
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 85);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data31']), 0, 'R');

      // 32
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 99.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data32']), 0, 'R');

      // 34
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 125.3);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data34']), 0, 'R');

      // 35
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 140.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['specified_donation']), 0, 'R');

      // 36
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 153);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['special_donation']), 0, 'R');

      // 37
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 163.7);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data37']), 0, 'R');

      // 38
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 173);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data38']), 0, 'R');

      // 39
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 182.5);
      $pdf->MultiCell(18, 5, number_format($datas['main']['Schedules14']['special_donation']), 0, 'R');

      // 40
      $pdf->SetFont($font, null, 6.8, true);
      $pdf->SetXY($x, 192);
      $pdf->MultiCell(18, 5, number_format($datas['main']['data40']), 0, 'R');

      $sub_sum = 0;
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){

        foreach ($datas['sub'] as $s_key => $sub) {
          $line = $s_key % 3;
          if($sub['NameList']['name']){
          // 日
          $y = date('Y',strtotime($sub['SpecifyDonation']['date'])) -1988;
          $m = date('n',strtotime($sub['SpecifyDonation']['date'])) ;
          $d = date('j',strtotime($sub['SpecifyDonation']['date'])) ;
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(32, 210.5 + $line * 4.8);
          $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

          // 寄付先
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(57, 210.5 + $line * 4.8);
          $pdf->MultiCell(37, 5, $sub['NameList']['name'], 0, 'C');

          // 告示番号
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(93, 210.5 + $line * 4.8);
          $pdf->MultiCell(37, 5, $sub['SpecifyDonation']['public_number'], 0, 'C');

          // 使途
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(129, 210.5 + $line * 4.8);
          $pdf->MultiCell(37, 5, $sub['SpecifyDonation']['purpose'], 0, 'C');

          // 金額
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(160, 210.5 + $line * 4.8);
          $pdf->MultiCell(37, 5, number_format($sub['SpecifyDonation']['sum']), 0, 'R');

          if ($line == 0) {
            // $y = date('Y',strtotime($term['Term']['account_beggining'])) -1988;
            // $m = date('n',strtotime($term['Term']['account_beggining'])) ;
            // $d = date('j',strtotime($term['Term']['account_beggining'])) ;
            //
            // $pdf->SetFont($font, null, 11, true);
            // $name = $y;
            // $pdf->SetXY(89, 15.5);
            // $pdf->MultiCell(38, 5, $name, 0, 'R');
            // $name = $m;
            // $pdf->SetXY(98, 15.5);
            // $pdf->MultiCell(38, 5, $name, 0, 'R');
            // $name = $d;
            // $pdf->SetXY(106, 15.5);
            // $pdf->MultiCell(38, 5, $name, 0, 'R');
            //
            // $y = date('Y',strtotime($term['Term']['account_end'])) -1988;
            // $m = date('n',strtotime($term['Term']['account_end'])) ;
            // $d = date('j',strtotime($term['Term']['account_end'])) ;
            //
            // $pdf->SetFont($font, null, 11, true);
            // $name = $y;
            // $pdf->SetXY(89, 20);
            // $pdf->MultiCell(38, 5, $name, 0, 'R');
            // $name = $m;
            // $pdf->SetXY(98, 20);
            // $pdf->MultiCell(38, 5, $name, 0, 'R');
            // $name = $d;
            // $pdf->SetXY(106, 20);
            // $pdf->MultiCell(38, 5, $name, 0, 'R');
            //
            // // 名称
            // $pdf->SetFont($font, null, 9, true);
            // $user_name = substr($user['name'],0,84);
            // $height = (mb_strwidth($user_name, 'utf8') <= 23) ? 17.5 : 14.8;
            // $pdf->SetXY(160.2, $height);
            // $pdf->MultiCell(37, 5, $user_name, 0, 'L');

            // 寄付金額41
            $sub_sum = 0;
            for ($i = 0; $i < 3; $i++) {
              if (empty($datas['sub'][$s_key + $i]['SpecifyDonation']['sum'])) break;
              $sub_sum += $datas['sub'][$s_key + $i]['SpecifyDonation']['sum'];
            }
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(160, 224.5);
            $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
          }
        }

          // 次ページ
          if ($line == 2 and !empty($datas['sub'][(int)$s_key + 1])) {
            $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14.pdf');
          }
        }
      } else {
        foreach ($datas['sub'] as $s_key => $sub) {
          $line = $s_key % 3;
          if($sub['NameList']['name']){
          // 日
          $y = date('Y',strtotime($sub['SpecifyDonation']['date'])) -1988;
          $m = date('n',strtotime($sub['SpecifyDonation']['date'])) ;
          $d = date('j',strtotime($sub['SpecifyDonation']['date'])) ;
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(27, 210 + $line * 4.8);
          $pdf->MultiCell(18, 5, $y.'・'.$m.'・'.$d, 0, 'C');

          // 寄付先
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(52, 210 + $line * 4.8);
          $text = substr($sub['NameList']['name'],0,36);
          $pdf->MultiCell(37, 5, $text, 0, 'C');

          // 告示番号
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(88, 210 + $line * 4.8);
          $pdf->MultiCell(37, 5, $sub['SpecifyDonation']['public_number'], 0, 'C');

          // 使途
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(124, 210 + $line * 4.8);
          $text = substr($sub['SpecifyDonation']['purpose'],0,36);
          $pdf->MultiCell(37, 5, $text, 0, 'C');

          // 金額
          $pdf->SetFont($font, null, 8, true);
          $pdf->SetXY(157, 210 + $line * 4.8);
          $pdf->MultiCell(37, 5, number_format($sub['SpecifyDonation']['sum']), 0, 'R');

          if ($line == 0) {

            // 寄付金額41
            $sub_sum = 0;
            for ($i = 0; $i < 3; $i++) {
              if (empty($datas['sub'][$s_key + $i]['SpecifyDonation']['sum'])) break;
              $sub_sum += $datas['sub'][$s_key + $i]['SpecifyDonation']['sum'];
            }
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(157, 224.5);
            $pdf->MultiCell(37, 5, number_format($sub_sum), 0, 'R');
          }
        }

          // 次ページ
          if ($line == 2 and !empty($datas['sub'][(int)$s_key + 1])) {
            $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules14.pdf');
          }
        }
      }

    }



      return $pdf;
    }

    /**
     * 繰延資産の償却額の計算に関する明細書
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules1606s($pdf, $font) {

      $model = ClassRegistry::init('FixedAsset');

        $Term  = ClassRegistry::init('Term');
        $datas = $model->findFor16_6();

        $point_start_y            = 46.5;   // 出力開始位置起点(縦)
        $point_step_y_row         = 13.5;   // 次の出力
        $point_step_x_next_record = 23;     //
        $point_y = $height = 18;          // 出力開始位置(縦)

        $record_count = 0;
        $balance_sum  = 0;

        //事業年度の月数を取得
        $pdf->SetFont($font, null, 5, true);
        $months = $model->getCurrentYear();
        $height = $point_y + 2;
        // $pdf->SetXY(66.4, 147.5);
        // $pdf->MultiCell(38, 5, $months, 0, 'L');

        $term_id = CakeSession::read('Auth.User.term_id');

        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
            )));

        // datasの振り分け
        $fixed = ['up' => [], 'down' => []];
        foreach ($datas as $data) {
          if ($data['AccountTitle']['closing_account_title'] == '長期前払費用') {
            $fixed['up'][] = $data;
          } else if ($data['AccountTitle']['small_group'] == '繰延資産') {
            $fixed['down'][] = $data;
          }
        }

        do {
          $start_count = ($page - 1) * 5;

          //事業年度で様式選択
          $term_info = $model->getCurrentTerm();
          $target_day = '2016/01/01';
          $target_day29 = '2017/04/01';
          if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'schedules16_6_e290401.pdf');
          } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules16_6.pdf');
          } else {
            $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules16_6.pdf');
          }

          $x_group_1 = 77.5;
          $x_group_2 = 77.5;
          $record_count=0;

          //Term.account_begginning
          $pdf->SetFont($font, null, 10, true);
          $account_beggining = $term['Term']['account_beggining'];
          $date_margin       = array(-4.8, -4.4, -3.6);
          $this->putHeiseiDate($pdf, 14.0, 123.0, $account_beggining, $date_margin, true);

          //Term.account_end
          $account_end = $term['Term']['account_end'];
          $this->putHeiseiDate($pdf, 18.4, 123.0, $account_end, $date_margin, true);

          // 法人名
          $user_name = CakeSession::read('Auth.User.name');
          $user_name = $this->roundLineStrByWidth($user_name, 28);
          $height    = (mb_strwidth($user_name, 'utf8') <= 28) ? 17.3 : 15.6;
          $align     = (mb_strwidth($user_name, 'utf8') <= 28) ? 'C' : 'L';
          $pdf->SetFont($font, null, 7.6, true);
          $pdf->SetXY(155.1, $height);
          $pdf->MultiCell(40, 5, $user_name, 0, $align);
          $pdf->SetFont($font, null, 6.8, true);

          $y = 46.5;

          for ($i = $start_count; $i < $start_count + 5; $i++) {
            if (empty($fixed['up'])) break;
            $data = array_shift($fixed['up']);

            $x = $x_group_1;
            //name row 1
            $height = $y;
            $pdf->SetFont($font, null, 8, true);
            $name = h($data['FixedAsset']['name']);
            $y_name = (mb_strwidth($name, 'utf8') <= 14) ? $height : $height - 1.5;
            $align  = (mb_strwidth($name, 'utf8') <= 14) ? 'C' : 'L';
            $name = mb_substr($name, 0, 14, "utf-8");
            $pdf->SetXY($x + 3.2 , $y_name);
            $pdf->MultiCell(22, 5, $name, 0, $align);


            //use date row 2
            $pdf->SetFont($font, null, 8, true);
            $height = 59;
            $this->_putDateConvGtJDate($pdf, $data['FixedAsset']['use_date'], $x, $height, $font);


            //cot or net_cost row 3
            $pdf->SetFont($font, null, 8, true);
            //$accounting_method = CakeSession::read('Auth.User.accounting_method');
            $sample = $model->getAccountInfo();
            $accounting_method = $sample['AccountInfo']['accounting_method'];
            $cost_x = null;
            //$cost_x = ($accounting_method == 1) ? number_format($data['FixedAsset']['cost']) : number_format($data['FixedAsset']['net_cost']);
            //2017年8月決算以降税込入力廃止
            if(strtotime('2017/8/31') <= strtotime($term_info['Term']['account_end'])){
              $cost_x = !empty($data['FixedAsset']['cost']) ? number_format($data['FixedAsset']['cost']) : null;
            } else {
              if ($accounting_method == 1) {
                  $cost_x = !empty($data['FixedAsset']['cost']) ? number_format($data['FixedAsset']['cost']) : null;
              } else if ($accounting_method == 2) {
                  $cost_x = !empty($data['FixedAsset']['net_cost']) ? number_format($data['FixedAsset']['net_cost']) : null;
              }
            }
            $cost_x = mb_substr($cost_x, 0, 13, "utf-8");
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x + 1, $height + 0.9);
            $pdf->MultiCell(26, 5, $cost_x, 0, 'C');

            //useful_life row 4
            $pdf->SetFont($font, null, 8.5, true);
            $useful_life = (int)($data['FixedAsset']['useful_life']) * 12;
            $height = $height + 14.5;
            $pdf->SetXY($x + 2, $height);
            $pdf->MultiCell(24, 5, $useful_life, 0, 'C');

            //current year row 5
            $months = $data['FixedAsset']['period'];
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x, $height);
            $pdf->MultiCell(28, 5, $months, 0, 'C');

            //pre_depreciation_sum row 6
            $pre_depreciation_sum = !empty($data['FixedAsset']['pre_depreciation_sum']) ? number_format($data['FixedAsset']['pre_depreciation_sum']) : null;
            $pre_depreciation_sum = mb_substr($pre_depreciation_sum, 0, 13, "utf-8");
            $height = $height + $point_step_y_row + 0.1;
            $pdf->SetXY($x + 1, $height);
            $pdf->MultiCell(26, 5, $pre_depreciation_sum, 0, 'C');

            //depreciation_sum row 7
            //$depreciation_sum = !empty($data['FixedAsset']['depreciation_sum']) ? number_format($data['FixedAsset']['depreciation_sum']) : null;
            $depreciation_sum = number_format($data['FixedAsset']['depreciation_sum']);
            $depreciation_sum = mb_substr($depreciation_sum, 0, 13, "utf-8");
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x, $height + 0.3);
            $pdf->MultiCell(28, 5, $depreciation_sum, 0, 'C');

            //shortage row 8
            $shortage = !empty($data['FixedAsset']['shortage']) ? number_format($data['FixedAsset']['shortage']) : null;
            $shortage = mb_substr($shortage, 0, 13, "utf-8");
            $height = $height + $point_step_y_row + 0.3;
            $pdf->SetXY($x, $height);
            $pdf->MultiCell(28, 5, $shortage, 0, 'C');

            //excess row 9
            $excess = !empty($data['FixedAsset']['excess']) ? number_format($data['FixedAsset']['excess']) : null;
            $excess = mb_substr($excess, 0, 13, "utf-8");
            $height = $height + $point_step_y_row + 0.1;
            $pdf->SetXY($x, $height);
            $pdf->MultiCell(28, 5, $excess, 0, 'C');

            //previous_excess_sum row 10
            $previous_excess_sum = !empty($data['FixedAsset']['previous_excess_sum']) ? number_format($data['FixedAsset']['previous_excess_sum']) : null;
            $previous_excess_sum = mb_substr($previous_excess_sum, 0, 13, "utf-8");
            $height = $height + $point_step_y_row - 0.1;
            $pdf->SetXY($x + 1, $height);
            $pdf->MultiCell(26, 5, $previous_excess_sum, 0, 'C');

            //tolerated row 11
            $tolerated = !empty($data['FixedAsset']['tolerated']) ? number_format($data['FixedAsset']['tolerated']) : null;
            $tolerated = mb_substr($tolerated, 0, 13, "utf-8");
            $height = $height + 13.7;
            $pdf->SetXY($x + 1, $height);
            $pdf->MultiCell(26, 5, $tolerated, 0, 'C');

            //next row 12
            // $next = !empty($data['next']) ? number_format($data['next']) : null;
            $next = number_format($data['next']);
            $next = mb_substr($next, 0, 13, "utf-8");
            $closing_account_title = h($data['AccountTitle']['closing_account_title']);
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x + 1, $height + 0.1);
            $pdf->MultiCell(26, 5, $next, 0, 'C');

            $x_group_1 += $point_step_x_next_record;
          }

          for ($i = $start_count; $i < $start_count + 5; $i++) {
            if (empty($fixed['down'])) break;
            $data = array_shift($fixed['down']);

            $x = $x_group_2;
            $height = 224.8;
            //name row 13
            $pdf->SetFont($font, null, 8, true);
            $name = h($data['FixedAsset']['name']);
            $y_name_small = (mb_strwidth($name, 'utf8') <= 14) ? $height - 0.1 : $height - 1.6;
            $align  = (mb_strwidth($name, 'utf8') <= 14) ? 'C' : 'L';
            $name = mb_substr($name, 0, 14, "utf-8");
            $pdf->SetXY($x + 3.2 , $y_name_small);
            $pdf->MultiCell(22, 5, $name, 0, $align);

            //cost or net_cost row 14
            $pdf->SetFont($font, null, 8.5, true);
            //$accounting_method = CakeSession::read('Auth.User.accounting_method');
            $sample = $model->getAccountInfo();
            $accounting_method = $sample['AccountInfo']['accounting_method'];
            if(strtotime('2017/8/31') <= strtotime($term_info['Term']['account_end'])){
              $cot_x = number_format($data['FixedAsset']['cost']);
            } else {
              $cot_x = ($accounting_method == 1) ? number_format($data['FixedAsset']['cost']) : number_format($data['FixedAsset']['net_cost']);
            }
            $cot_x = mb_substr($cot_x, 0, 13, "utf-8");
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x + 1, $height);
            $pdf->MultiCell(26, 5, $cot_x, 0, 'C');

            //pre_depreciation_sum row 15
            $pre_depreciation_sum = number_format($data['FixedAsset']['previous_depreciation']);
            $pre_depreciation_sum = mb_substr($pre_depreciation_sum, 0, 13, "utf-8");
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x + 1, $height - 0.2);
            $pdf->MultiCell(26, 5, $pre_depreciation_sum, 0, 'C');

            //depreciation_sum row 16
            $depreciation_sum = !empty($data['FixedAsset']['depreciation_sum']) ? number_format($data['FixedAsset']['depreciation_sum']) : null;
            $depreciation_sum = mb_substr($depreciation_sum, 0, 13, "utf-8");
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x + 1, $height + 0.2);
            $pdf->MultiCell(26, 5, $depreciation_sum, 0, 'C');

            //end_price row 17
            $end_price = !empty($data['FixedAsset']['end_price']) ? $data['FixedAsset']['end_price'] : null;
            // $cost14 = ($accounting_method == 1) ? $data['FixedAsset']['cost'] : $data['FixedAsset']['net_cost'];
            // if($data['FixedAsset']['previous_depreciation']){
            //   $end_price = $data['FixedAsset']['previous_depreciation'] - $data['FixedAsset']['depreciation_sum'];
            // } else {
            //   $end_price = $cost14 - $data['FixedAsset']['depreciation_sum'];
            // }
            $end_price = number_format($end_price);
            $end_price = mb_substr($end_price, 0, 13, "utf-8");
            $pdf->SetAutoPageBreak(false, 0);
            $height = $height + $point_step_y_row;
            $pdf->SetXY($x, $height + 0.2);
            $pdf->MultiCell(28, 4, $end_price, 0, 'C');

            $x_group_2 += $point_step_x_next_record;
          }

        } while(!empty($fixed['up']) or !empty($fixed['down']));

        $x_group_1 = 77.5;
        $x_group_2 = 77.5;
        $count1 = 0;
        $count2 = 0;
        $datas = [];
        foreach ($datas as $key => $data) {

            if (($data['AccountTitle']['closing_account_title'] == '長期前払費用') && ($data['FixedAsset']['depreciation_method'] == '均等償却')) {
                $count1++;


            } else if ($data['AccountTitle']['small_group'] == '繰延資産') {
                $count2++;


            }
        }

        return $pdf;
    }

    /**
     * 道府県民税・事業税・地方法人特別税の確定申告書
     * @param FPDI  $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_prefecture_return($pdf, $font) {

      $Schedules14      = ClassRegistry::init('Schedules14');

      //事業年度で様式選択
      $term_info = $Schedules14->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day2804 = '2016/04/01';
      if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
        $template         = $this->setTemplateAddPage($pdf, $font, 'sH280401prefecture_return.pdf');
      } else if(strtotime($target_day) <= strtotime($term_info['Term']['account_beggining'])){
        $template         = $this->setTemplateAddPage($pdf, $font, 's280401prefecture_return.pdf');
      } else {
        $template         = $this->setTemplateAddPage($pdf, $font, 's270401-1231prefecture_return6.pdf');
      }

        $Schedules7       = ClassRegistry::init('Schedules7');
        $PrefectureReturn = ClassRegistry::init('PrefectureReturn');
        $Schedules4       = ClassRegistry::init('Schedules4');
        $FixedAsset       = ClassRegistry::init('FixedAsset');
        $Schedules168      = ClassRegistry::init('Schedules168');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        //欠損金当期控除額の値を取得
        $data7 = $Schedules7->findForIndex7($preSum,$data14['not_cost']);

        //申告書に表示するデータ
        $data = $PrefectureReturn->findFor6($preSum,$data14['not_cost'],$data7['this_deduction_sum']);

        $point_start_x = 88;       // 出力開始位置起点(縦)
        $point_start_y = 37.4;     // 出力開始位置起点(縦)
        $point_step    = 12.7;     // 次の出力
        $x_left_row    = 40.1;
        $x_step_row    = 6.8;
        $margin        = array(2.9, 2.0);
        $y_left_row_start = 76.7;
        $x_margin      = 2.3;
        $round_hund    = 100;
        $round_thou    = 1000;

        //提出先
        $account_info = $PrefectureReturn->getAccountInfo();
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(32, 22.2);
        if($account_info['AccountInfo']['office_prefecture']){
          $pdf->MultiCell(50, 5, h($account_info['AccountInfo']['office_prefecture']).'長', 0, 'R');
        }

        //法人番号
        $pdf->SetFont($font, null, 7.1, true);
        $company_number = h($data['user']['User']['company_number']);
        $this->_putNumberTableItem($pdf, $company_number, 75.5 + $x_margin*3, 23, array(2.32, 2.32, 2.32));

        //phone number row left 1
        $pdf->SetFont($font, null, 8, true);
        $phone_number = h($data['user']['NameList']['phone_number']);
        $phone_number = mb_substr($phone_number, 0, 13, "utf-8");
        $pdf->SetXY($point_start_x + 2, $point_start_y);
        $pdf->MultiCell(28, 5, $phone_number, 0, 'C');

        //Namelist.prefecture + city + address row left 2
        $pdf->SetFont($font, null, 8.8, true);
        $name_list = h($data['user']['NameList']['prefecture']) . h($data['user']['NameList']['city']) .  h($data['user']['NameList']['address']);
        $name_list = mb_substr($name_list, 0, 60, "utf-8");
        $height = (mb_strwidth($name_list, 'utf8') <= 60) ? 31.4 : 28.6;
        $align  = (mb_strwidth($name_list, 'utf8') <= 60) ? 'C' : 'L';
        $pdf->SetXY(28.2, $height);
        $pdf->MultiCell(97, 5, $name_list, 0, $align);

        //user.NameList.name_furigana row left 3
        $pdf->SetFont($font, null, 5, true);
        $name_furigana = h($data['user']['NameList']['name_furigana']);
        $name_furigana = mb_substr($name_furigana, 0, 52, "utf-8");
        $pdf->SetXY(27.2, 41.5);
        $pdf->MultiCell(97, 5, $name_furigana, 0, 'C');
        $pdf->SetFont($font, null, 7, true);

        //user.NameList.name row left 3
        $name = isset($data['user']['NameList']['name']) ? h($data['user']['NameList']['name']) : null;
        $pdf->SetXY(27.2, 45.3);
        $name = mb_substr($name, 0, 38, "utf-8");
        $pdf->MultiCell(97, 5, $name, 0, 'C');

        //Term.account_beggining row left 4
        $y1 = date('Y',strtotime($data['user']['Term']['account_beggining'])) -1988;
        $m1 = date('n',strtotime($data['user']['Term']['account_beggining'])) ;
        $d1 = date('j',strtotime($data['user']['Term']['account_beggining'])) ;
        $this->_putNumberTableItem($pdf, $y1, -3.7, 60, $margin);
        $this->_putNumberTableItem($pdf, $m1, 5.5, 60, $margin);
        $this->_putNumberTableItem($pdf, $d1, 14.7, 60, $margin);

        //Term.account_end row left 5
        $y2 = date('Y',strtotime($data['user']['Term']['account_end'])) -1988;
        $m2 = date('n',strtotime($data['user']['Term']['account_end'])) ;
        $d2 = date('j',strtotime($data['user']['Term']['account_end'])) ;
        $this->_putNumberTableItem($pdf, $y2, 31.0, 60, $margin);
        $this->_putNumberTableItem($pdf, $m2, 40.1, 60, $margin);
        $this->_putNumberTableItem($pdf, $d2, 49.5, 60, $margin);

        //shotoku_sum row left 6
        $pdf->SetFont($font, 'B', 7.8, true);
        $shotoku_sum = (int)($data['shotoku_sum']);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $height = 71.2;
        } else {
          $height = 71.5;
        }
        $this->_putNumberTableItem($pdf, $shotoku_sum, $x_left_row + $x_margin * 3, $height, $margin);

        //under400Base row left 7
        $under400Base = (int)($data['under400Base']);
        $this->_putNumberTableItem($pdf, $under400Base, $x_left_row, $y_left_row_start, $margin, $round_thou);

        //over400Base row left 8
        $over400Base = (int)($data['over400Base']);
        $height = $y_left_row_start + 5.7;
        $this->_putNumberTableItem($pdf, $over400Base, $x_left_row, $height, $margin, $round_thou);

        //over800Base row left 9
        $height += 5.8;
        $over800Base = (int)($data['over800Base']);
        $this->_putNumberTableItem($pdf, $over800Base, $x_left_row, $height, $margin, $round_thou);

        //base_sum row left 9
        $height += 5.9;
        $base_sum = (int)($data['base_sum']);
        $this->_putNumberTableItem($pdf, $base_sum, $x_left_row, $height-0.1, $margin, $round_thou);

        //under400_rate row left 10
        $pdf->SetFont($font, null, 8, true);
        $under400_rate = h($data['under400_rate']);
        $pdf->SetXY(73, $y_left_row_start);
        $pdf->MultiCell(28, 5, $under400_rate, 0, 'C');

        //over400_rate row left 11
        $over400_rate = h($data['over400_rate']);
        $height2 = $y_left_row_start + 5.8;
        $pdf->SetXY(73, $height2);
        $pdf->MultiCell(28, 5, $over400_rate, 0, 'C');

        //over800_rate row left 12
        $over800_rate = h($data['over800_rate']);
        $height2 += 5.8;
        $pdf->SetXY(73, $height2);
        $pdf->MultiCell(28, 5, $over800_rate, 0, 'C');

        //middle_business_tax row left 13
        $y_adjust = 0;
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y_adjust = 5.6;
        }
        $x2 = 81.8 + $x_margin;
        $middle_business_tax = (int)($data['middle_business_tax']);
        $this->_putNumberTableItem($pdf, $middle_business_tax, $x2, 151.4 + $y_adjust, $margin, $round_hund);

        //real_business_tax row left 14
        $pdf->SetFont($font, null, 8, true);
        $real_business_tax = (int)($data['real_business_tax']);
        $this->_putNumberTableItem($pdf, $real_business_tax, $x2, 157.5+ $y_adjust, $margin, $round_hund);
        $this->_putNumberTableItem($pdf, $real_business_tax, $x_left_row - $x_margin * 3, 163.3+ $y_adjust, $margin, $round_hund);
        $this->_putNumberTableItem($pdf, $real_business_tax, $x2 + $x_margin * 2, 175+ $y_adjust, $margin);

        //special_tax_rate row left 15
        $special_tax_rate = h($data['special_tax_rate']);
        $pdf->SetXY(73.2, 187+ $y_adjust);
        $pdf->MultiCell(28, 5, $special_tax_rate, 0, 'C');

        //special_tax_sum row left 16
        $special_tax_sum = (int)($data['special_tax_sum']);
        $this->_putNumberTableItem($pdf, $special_tax_sum, $x2, 187+ $y_adjust, $margin, $round_hund);
        $this->_putNumberTableItem($pdf, $special_tax_sum, $x2, 197.9+ $y_adjust, $margin, $round_hund);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y2 = 203.7;
          $this->_putNumberTableItem($pdf, $special_tax_sum, $x2, $y2+ $y_adjust, $margin, $round_hund);
        }

        //middle_special_tax row left 17
        $middle_special_tax = (int)($data['middle_special_tax']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $middle_special_tax,33.8, 215.1, $margin, $round_hund);
        } else {
          $y2 = 203.9;
          $this->_putNumberTableItem($pdf, $middle_special_tax, $x2, $y2+ $y_adjust, $margin, $round_hund);
        }

        //real_special_tax row left 18
        $real_special_tax = (int)($data['real_special_tax']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y2 = $y2 + 5.8 *2;
          $this->_putNumberTableItem($pdf, $real_special_tax, 33.8, $y2+ $y_adjust, $margin, $round_hund);
          $y2 = $y2 + 5.8;
          $this->_putNumberTableItem($pdf,$real_special_tax, 33.8 + $x_margin * 2, $y2+ $y_adjust, $margin);
        } else {
          $y2 = $y2 + 5.8;
          $this->_putNumberTableItem($pdf, $real_special_tax, $x2, $y2+ $y_adjust, $margin, $round_hund);
          $y2 = $y2 + 5.8;
          $this->_putNumberTableItem($pdf, $real_special_tax, $x2 + $x_margin * 2, $y2+ $y_adjust, $margin);
        }

        //pre_shotoku row left 19
        $pdf->SetFont($font, null, 8, true);
        $pre_shotoku = (int)($data['pre_shotoku']);
        $y2 = $y2 + 5.8;
        $this->_putNumberTableItemNon0($pdf, $pre_shotoku, $x2 + $x_margin * 2, $y2+ $y_adjust, $margin);

        //shotokuzei row left 20
        $shotokuzei = (int)($data['shotokuzei']);
        $y2 = $y2 + 5.8;
        $this->_putNumberTableItemNon0($pdf, $shotokuzei, $x2 + $x_margin * 2, $y2+ $y_adjust, $margin);

        //preSum row left 21
        $preSum = (int)($data['preSum']);
        $y2 = $y2 + 5.8 * 4;
        $this->_putNumberTableItemNon0($pdf, $preSum, $x2 + $x_margin * 2, $y2+ $y_adjust, $margin);

        //kessonkin_koujyo row left 22
        $kessonkin_koujyo =(int)($data['kessonkin_koujyo']);
        $y2 = $y2 + 5.8;
        $this->_putNumberTableItemNon0($pdf, $kessonkin_koujyo, $x2 + $x_margin * 2, $y2+ $y_adjust, $margin);

        //shotoku row left
        $shotoku = (int)($data['shotoku']['income']);
        $y2 = $y2 + 4.6;
        $this->_putNumberTableItem($pdf, $shotoku, $x2 + $x_margin * 2, $y2+ $y_adjust, $margin);

        //confirm_date row left 24
        $pdf->SetFont($font, null, 7, true);
        $y = date('Y',strtotime($data['confirm_date'])) -1988;
        $m = date('n',strtotime($data['confirm_date'])) ;
        $d = date('j',strtotime($data['confirm_date'])) ;
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y2 = $y2 -27.9;
          $pdf->SetXY(141.7, $y2);
          $pdf->MultiCell(28, 5, $y, 0, 'R');
          $pdf->SetXY(148, $y2);
          $pdf->MultiCell(28, 5, $m, 0, 'R');
          $pdf->SetXY(155.1, $y2);
          $pdf->MultiCell(28, 5, $d, 0, 'R');
        } else {
          $pdf->SetXY(19.2, 267.9);
          $pdf->MultiCell(28, 5, $y, 0, 'R');
          $pdf->SetXY(26.3, 267.9);
          $pdf->MultiCell(28, 5, $m, 0, 'R');
          $pdf->SetXY(33.8, 267.9);
          $pdf->MultiCell(28, 5, $d, 0, 'R');
        }

        // Right
        //User.business right row 1
        $business = h($data['user']['User']['business']);
        $business = mb_substr($business, 0, 14, "utf-8");
        $pdf->SetFont($font, null, 7, true);
        $pdf->SetXY(145.8, 28);
        $pdf->MultiCell(40, 2, $business, 0, 'C');

        //capital right row 2
        $pdf->SetFont($font, null, 8, true);
        $capital = (int)($data['capital']);
        $x_right = 146.4;
        $this->_putNumberTableItem($pdf, $capital, $x_right + $x_margin * 3, 35, $margin);

        //capital_sum right row 3
        $capital_sum = (int)($data['capital_sum']);
        $y_right_start = 48.5;
        $this->_putNumberTableItem($pdf, $capital_sum, $x_right + $x_margin * 3, $y_right_start, $margin);

        //houjinzei_shihonkintou right row 4
        $houjinzei_shihonkintou = (int)($data['houjinzei_shihonkintou']);
        $y_right = $y_right_start;
        $this->_putNumberTableItem($pdf, $houjinzei_shihonkintou, $x_right + $x_margin * 3, $y_right + 5.5, $margin);

        //確定 right row 5 TODO: don't know
        $pdf->SetFont($font, 'B', 7, true);
        $pdf->SetXY(127, 60.1);
        $pdf->MultiCell(28, 2, '確定', 0, 'C');
        $pdf->SetXY(127, 60.1);
        $pdf->MultiCell(28, 2, '確定', 0, 'C');

        //pre_base_houjinzei row 6
        $pdf->SetFont($font, null, 8, true);
        $y_right = $y_right_start + 5.8 * 4;
        $pre_base_houjinzei = (int)($data['pre_base_houjinzei']);
        $this->_putNumberTableItem($pdf, $pre_base_houjinzei, $x_right + $x_margin * 3, $y_right - 0.2, $margin);

        //under400 row 7
        $y_right += 5.8;
        $x_right2 = 81.8 + $x_margin;
        $pdf->SetFont($font, null, 8, true);
        $under400 = isset($data['under400']) ? (int)($data['under400']) : null;
        $this->_putNumberTableItem($pdf, $under400, $x_right2, $y_right-0.5, $margin, $round_hund);

        //over400 row 8
        $over400 = isset($data['over400']) ? (int)($data['over400']) : null;
        $y_right += 5;
        $this->_putNumberTableItem($pdf, $over400, $x_right2, $y_right, $margin, $round_hund);

        //over800 row 9
        $over800 = isset($data['over800']) ? (int)($data['over800']) : null;
        $y_right += 5.8;
        $this->_putNumberTableItem($pdf, $over800, $x_right2, $y_right, $margin, $round_hund);

        //business_tax row 10
        $business_tax = isset($data['business_tax']) ? (int)($data['business_tax']) : null;
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y_adjust = 5.6;
          $this->_putNumberTableItem($pdf, $business_tax, 33.8, 157.3, $margin, $round_hund);
        }
        $y_right += 5.8;
        $this->_putNumberTableItem($pdf, $business_tax, $x_right2, $y_right, $margin, $round_hund);
        $this->_putNumberTableItem($pdf, $business_tax, $x_right2, 140, $margin, $round_hund);
        $this->_putNumberTableItem($pdf, $business_tax, $x_left_row + $x_margin, 186.6 + $y_adjust, $margin, $round_hund);

        //base_houjinzei row 11
        $base_houjinzei = (int)($data['base_houjinzei']);
        $y_right += 5.8;
        if(strtotime($target_day) <= strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $base_houjinzei, $x_right, $y_right -5.75, $margin, $round_thou);
        } else {
          $this->_putNumberTableItem($pdf, $base_houjinzei, $x_right, $y_right, $margin, $round_thou);
        }

        //houjinzei row 12
        $houjinzeiwari = (int)($data['houjinzeiwari']);
        $y_right += 5.8 * 2;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $houjinzeiwari, $x_right + $x_margin * 3, $y_right, $margin);
        } else {
          $this->_putNumberTableItem($pdf, $houjinzeiwari, $x_right + $x_margin * 3, $y_right -5.5, $margin);
        }

        //houjinzeiwari_rate
        $pdf->SetFont($font, null, 4.5, true);
        $houjinzeiwari_rate = ($data['houjinzeiwari_rate']);
        $houjinzeiwari_rate = mb_substr($houjinzeiwari_rate, 0, 3, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($x_right - 7.2, $y_right + 1.0);
        } else {
          $pdf->SetXY($x_right - 7, $y_right - 4.8);
        }
        $pdf->MultiCell(20, 5, $houjinzeiwari_rate, 0, 'C');

        //sashihiki_houjinzeiwari row 13
        $pdf->SetFont($font, null, 8, true);
        $sashihiki_houjinzeiwari = (int)($data['sashihiki_houjinzeiwari']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y_right += 5.8 * 3;
        } else {
          $y_right += 5.8 * 4;
        }
        $this->_putNumberTableItem($pdf, $sashihiki_houjinzeiwari, $x_right + $x_margin, $y_right, $margin, $round_hund);

        //middle_houjinzeiwari row 14
        $middle_houjinzeiwari = (int)($data['middle_houjinzeiwari']);
        $y_right += 5.8;
        $this->_putNumberTableItem($pdf, $middle_houjinzeiwari, $x_right + $x_margin, $y_right, $margin, $round_hund);

        //real_houjinzeiwari row 15
        $real_houjinzeiwari = (int)($data['real_houjinzeiwari']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y_right += 5.7 * 2;
        } else {
          $y_right += 5.8 * 3;
        }
        $this->_putNumberTableItem($pdf, $real_houjinzeiwari, $x_right + $x_margin, $y_right, $margin, $round_hund);

        //months row 16
        $months = h($data['months']);
        $y_right += 5.8;
        $pdf->SetXY($x_right + 25.7, $y_right);
        $pdf->MultiCell(20, 5, $months, 0, 'C');

        //kintouwari row 16
        $kintouwari = (int)($data['kintouwari']);
        $y_right += 5.9;
        $this->_putNumberTableItem($pdf, $kintouwari, $x_right + $x_margin, $y_right, $margin, $round_hund);

        //kintouwari_base row 17
        $kintouwari_base = number_format($data['kintouwari_base']);
        $kintouwari_base = mb_substr($kintouwari_base, 0, 9, "utf-8");
        $pdf->SetXY($x_right - 24.2, $y_right - 0.6);
        $pdf->MultiCell(28, 5, $kintouwari_base, 0, 'C');

        //middle_kintouwari row 18
        $middle_kintouwari = (int)($data['middle_kintouwari']);
        $y_right += 5.3;
        $this->_putNumberTableItem($pdf, $middle_kintouwari, $x_right + $x_margin, $y_right, $margin, $round_hund);

        //real_kintouwari row 19
        $real_kintouwari = (int)($data['real_kintouwari']);
        $y_right += 5.8;
        $this->_putNumberTableItem($pdf, $real_kintouwari, $x_right + $x_margin, $y_right, $margin, $round_hund);

        //real_prefecture_tax row 19
        $real_prefecture_tax = (int)($data['real_prefecture_tax']);
        $y_right += 5.8;
        $this->_putNumberTableItem($pdf, $real_prefecture_tax, $x_right + $x_margin, $y_right, $margin, $round_hund);
        $y_right += 5.8 * 2;
        $this->_putNumberTableItem($pdf, $real_prefecture_tax, $x_right + $x_margin*3, $y_right, $margin);

        //tax_accountant_phone row 20
//        $tax_accountant_phone = h($data['tax_accountant_phone']);
//        $this->_putNumberTableItem($pdf, $tax_accountant_phone, $x_right + $x_margin*3, 267.5, $margin);

        //chukan_kanpu row 21
        $chukan_kanpu = (int)($data['chukan_kanpu']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $chukan_kanpu, $x_right + $x_margin * 3, 215.5, $margin);
        } else {
          $this->_putNumberTableItem($pdf, $chukan_kanpu, $x_right + $x_margin * 3, 262, $margin);
        }

        //branch_name row 21
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $y_adjust = -52.6;
        }
        $pdf->SetFont($font, null, 4.5, true);
        $branch_name = h($data['branch_name']);
        $branch_name = mb_substr($branch_name, 0, 7, "utf-8");
        $pdf->SetXY($x_right + 17.9, 272.9 + $y_adjust);
        $pdf->MultiCell(28, 2, $branch_name, 0, 'C');

        //bank_name row 22
        $pdf->SetFont($font, null, 5.2, true);
        $bank_name = h($data['bank_name']);
        $bank_name = mb_substr($bank_name, 0, 8, "utf-8");

        $pdf->SetXY($x_right-0.1, 272.7 + $y_adjust);
        $pdf->MultiCell(28, 2, $bank_name, 0, 'C');

        //account_number row 23
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetFont($font, null, 7, true);
        $account_number = (int)($data['account_number']);
        if (!empty($account_number)) {
            $account_number = mb_substr($account_number, 0, 7, "utf-8");
            $pdf->SetXY($x_right + 20, 275 + $y_adjust);
            $pdf->MultiCell(16, 2, $account_number, 0, 'R');
        }

        //account_class
        $pdf->SetFont($font, null, 12, true);
        $account_class = $data['account_class'];
        if (!empty($account_class)) {
            $x = ($account_class == '普通') ? $x_right + 2.8 : $x_right + 7.2;
            $pdf->SetXY($x, 273.7 + $y_adjust);
            $pdf->MultiCell(28, 5, '◯', 0, 'C');
        }

        //houjinzei_shihonkintou right row
        $pdf->SetFont($font, null, 8, true);
        $houjinzei_shihonkintou = (int)($data['houjinzei_shihonkintou']);
        $this->_putNumberTableItem($pdf, $houjinzei_shihonkintou, $x_right + $x_margin * 3, 279.7+ $y_adjust, $margin);

        //kakuteizeigaku
        $kakuteizeigaku = (int)($data['kakuteizeigaku']);
        $this->_putNumberTableItem($pdf, $kakuteizeigaku, $x_right + $x_margin * 3, 285+ $y_adjust, $margin);

        //kanpu_message
        $kanpu_message = isset($data['kanpu_message']) ? h($data['kanpu_message']) : null;
        $pdf->SetFont($font, null, 9.5, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(108, 284);
        }
        $pdf->MultiCell(80, 5, $kanpu_message, 0, 'R');


        //extension_jigyouz
        $extension_jigyouz = isset($data['extension_jigyouzei']) ? $data['extension_jigyouzei'] : null;

        $pdf->SetFont($font, null, 12, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $x = (!empty($extension_jigyouz) &&  $extension_jigyouz != '無') ? 152 : 156.5;
          $pdf->SetXY($x, 254.5);
        } else if(strtotime($target_day) <= strtotime($term_info['Term']['account_beggining'])){
          $x = (!empty($extension_jigyouz) &&  $extension_jigyouz != '無') ? 31.9 : 36.5;
          $pdf->SetXY($x+0.2, 278.2);
        } else {
          $x = (!empty($extension_jigyouz) &&  $extension_jigyouz != '無') ? 31.9 : 36.5;
          $pdf->SetXY($x, 278.2);
        }
        $pdf->MultiCell(28, 2, '◯', 0, 'C');

        //extension_houjinzei
        $extension_houjinzei = isset($data['extension_houjinzei']) ? $data['extension_houjinzei'] : null;

        $pdf->SetFont($font, null, 12, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $x = (!empty($extension_houjinzei) && ($extension_houjinzei != '無')) ? 168 : 172.4;
          $pdf->SetXY($x+0.2, 254.5);
        } else if(strtotime($target_day) <= strtotime($term_info['Term']['account_beggining'])){
          $x = (!empty($extension_houjinzei) && ($extension_houjinzei != '無')) ? 47.8 : 52.8;
          $pdf->SetXY($x+0.2, 278.2);
        } else {
          $x = (!empty($extension_houjinzei) && ($extension_houjinzei != '無')) ? 47.8 : 52.8;
          $pdf->SetXY($x, 278.2);
        }
        $pdf->MultiCell(28, 2, '◯', 0, 'C');

        //return_class
        $return_class = h($data['user']['User']['return_class']);
        if($return_class == '1') {
            $x_return_class = 91.3;
        } else if ($return_class == '2') {
            $x_return_class = 100.8;
        }
        $y_return_class = !empty($return_class) ? 277 : 275.6;
        $_font          = !empty($return_class) ? 18 : 25.5;
        $pdf->SetFont($font, null, $_font, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          if($return_class == '1') {
              $x_return_class = 153.8;
          } else if ($return_class == '2') {
              $x_return_class = 162.7;
          }
          $pdf->SetXY($x_return_class, 259);
        } else if(strtotime($target_day) <= strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($x_return_class, $y_return_class-0.3);
        } else {
          $pdf->SetXY($x_return_class, $y_return_class);
        }
        $pdf->MultiCell(28, 5, '◯', 0, 'C');

        //chukan_youhi
        $pdf->SetFont($font, null, 12.8, true);
        $chukan_youhi = h($data['chukan_youhi']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $x_chukan = !empty($chukan_youhi) ? 154 : 163.8;
          $pdf->SetXY($x_chukan, 271.7);
        } else {
          $x_chukan = !empty($chukan_youhi) ? 77.2 : 81.5;
          $pdf->SetXY($x_chukan, 283.8);
        }
        $pdf->MultiCell(28, 2, '◯', 0, 'C');

        //tax_accountant_phone
        $pdf->SetFont($font, null, 5.6, true);
        $tax_accountant_phone = h($data['tax_accountant_phone']);
        $tax_accountant_phone = mb_substr($tax_accountant_phone, 0, 12, "utf-8");
        $tax_accountant_phone = str_replace("-"," ",$tax_accountant_phone);
        $y = 0;
        if (!empty($tax_accountant_phone)) {
            for ($i = 0; $i <= strlen($tax_accountant_phone) - 1; $i++) {
            $element = mb_substr($tax_accountant_phone, $i, 1, 'utf-8');
            if ($element == '-') {
                $element = ' ';
            }
            if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY(189.7, 249.8 + $y);
            } else {
              $pdf->SetXY(189.7, 254.8 + $y);
            }
            $pdf->MultiCell(3, 2, $element, 0, $align);
            $y += 2.4;
        }
//            $pdf->SetXY(189.7, 254.8);
//            $pdf->MultiCell(3, 1, $tax_accountant_phone, 0, 'C');
        }

        return $pdf;
    }

    /**
     * 市町村民税の確定申告
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_city20($pdf, $font) {

      $CityReturn  = ClassRegistry::init('CityReturn');

      //事業年度で様式選択
      $term_info = $CityReturn->getCurrentTerm();
      $target_day = '2016/01/01';
      if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 's271231_city_return.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101city_returns.pdf');
      }

        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7  = ClassRegistry::init('Schedules7');
        $Schedules4  = ClassRegistry::init('Schedules4');
        $FixedAsset  = ClassRegistry::init('FixedAsset');
        $Schedules168 = ClassRegistry::init('Schedules168');

        //提出先
        $account_info = $CityReturn->getAccountInfo();
        if($account_info['AccountInfo']['office_city']){
          $pdf->SetFont($font, null, 9, true);
          if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
            $pdf->SetXY(51, 19.6);
          } else {
            $pdf->SetXY(48, 24.7);
          }
          $pdf->MultiCell(76, 5, h($account_info['AccountInfo']['office_city']).'長　殿', 0, 'L');
        }

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }
        $data7 = $Schedules7->findForIndex7($preSum,$data14['not_cost']);
        $data   = $CityReturn->findFor20($preSum, $data14['not_cost'], $data7['this_deduction_sum']);

        $point_start_x = 88;       // 出力開始位置起点(縦)
        $point_start_y = 40;     // 出力開始位置起点(縦)
        $point_step    = 12.7;     // 次の出力
        $x_left_row    = 47.2;
        $y_left_row_start = 76.5;
        $x_margin = 2.5;
        $round_thou = 1000;
        $round_hund = 100;

        //法人番号
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        } else {
          $pdf->SetFont($font, null, 8, true);
          $company_number = h($data['user']['User']['company_number']);
          $this->_putNumberTableItem($pdf, $company_number, 141 + $x_margin*3, 24, array(2.56, 2.56, 2.56));
        }

        //phone number row left 1
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetTextColor(6, 6, 6);
        $phone_number = h($data['user']['NameList']['phone_number']);
        $phone_number = mb_substr($phone_number, 0, 13, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($point_start_x + 3, $point_start_y-0.5);
        } else {
          $height2016 = 4.3;
          $pdf->SetXY($point_start_x + 3, $point_start_y + $height2016);
        }
        $pdf->MultiCell(28, 5, $phone_number, 0, 'C');

        //Namelist.prefecture + city + address row left 2
        $pdf->SetFont($font, null, 9.2, true);
        $name_list = h($data['user']['NameList']['prefecture']) . h($data['user']['NameList']['city']) .  h($data['user']['NameList']['address']);
        $name_list = mb_substr($name_list, 0, 60, "utf-8");
        $height = (mb_strwidth($name_list, 'utf8') <= 60) ? 31.4 : 28.9;
        $align  = (mb_strwidth($name_list, 'utf8') <= 60) ? 'C' : 'L';
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(27.7, $height);
        } else {
          $pdf->SetXY(25, $height + $height2016);
        }
        $pdf->MultiCell(100, 5, $name_list, 0, $align);

        //user.NameList.name_furigana row left 3
        $pdf->SetFont($font, null, 6, true);
        $name_furigana = h($data['user']['NameList']['name_furigana']);
        $name_furigana = mb_substr($name_furigana, 0, 46, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(27.2, 44.8);
        } else {
          $pdf->SetXY(24.2, 44.8 + $height2016);
        }
        $pdf->MultiCell(100, 5, $name_furigana, 0, 'C');
        $pdf->SetFont($font, null, 7, true);

        //user.NameList.name row left
        $pdf->SetFont($font, null, 9.2, true);
        $name = isset($data['user']['NameList']['name']) ? h($data['user']['NameList']['name']) : null;
        $name = mb_substr($name, 0, 60, "utf-8");
        $height = (mb_strwidth($name, 'utf8') <= 60) ? 52.3 : 50.5;
        $align  = (mb_strwidth($name, 'utf8') <= 60) ? 'C' : 'L';
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(27.7, $height);
        } else {
          $pdf->SetXY(24.7, $height + $height2016);
        }
        $pdf->MultiCell(100, 5, $name, 0, $align);

        //president.NameList.name_furigana row left 4
        $pdf->SetFont($font, null, 5, true);
        $name_furigana = h($data['president']['NameList']['name_furigana']);
        $name_furigana = mb_substr($name_furigana, 0, 25, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(20.2, 61.8);
        } else {
          $pdf->SetXY(17.2, 61.8 + $height2016);
        }
        $pdf->MultiCell(60, 5, $name_furigana, 0, 'C');

        //president.NameList.name row left 5
        $name = h($data['president']['NameList']['name']);
        $name = mb_substr($name, 0, 15, "utf-8");
        $pdf->SetFont($font, null, 8.5, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(20.2, 67.2);
        } else {
          $pdf->SetXY(17.2, 67.2 + $height2016);
        }
        $pdf->MultiCell(60, 5, $name, 0, 'C');

        //accounting_officer_furigana row left 6
        $accounting_officer_furigana = h($data['accounting_officer_furigana']);
        $pdf->SetFont($font, null, 5, true);
        $accounting_officer_furigana = mb_substr($accounting_officer_furigana, 0, 22, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($point_start_x - 6.5, 61.8);
        } else {
          $pdf->SetXY($point_start_x - 8.5, 61.8 + $height2016);
        }
        $pdf->MultiCell(50, 5, $accounting_officer_furigana, 0, 'C');

        //accounting_officer row left 7
        $accounting_officer = h($data['accounting_officer']);
        $pdf->SetFont($font, null, 7.8, true);
        $accounting_officer = mb_substr($accounting_officer, 0, 15, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($point_start_x - 6.5, 67.0);
        } else {
          $pdf->SetXY($point_start_x - 8.5, 67.0 + $height2016);
        }
        $pdf->MultiCell(50, 5, $accounting_officer, 0, 'C');

        //Term.account_beggining row left 8
        $pdf->SetFont($font, null, 7, true);
        $y2 = date('Y',strtotime($data['user']['Term']['account_beggining'])) -1988;
        $m2 = date('n',strtotime($data['user']['Term']['account_beggining'])) ;
        $d2 = date('j',strtotime($data['user']['Term']['account_beggining'])) ;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $y2, -4.5, 74);
          $this->_putNumberTableItem($pdf, $m2, 5.7, 74);
          $this->_putNumberTableItem($pdf, $d2, 16.0, 74);
        } else {
          $this->_putNumberTableItem($pdf, $y2, -8, 74 + $height2016-0.2);
          $this->_putNumberTableItem($pdf, $m2, 2.2, 74 + $height2016-0.2);
          $this->_putNumberTableItem($pdf, $d2, 12.5, 74 + $height2016-0.2);
        }

        //Term.account_end row left 9
        $y2 = date('Y',strtotime($data['user']['Term']['account_end'])) -1988;
        $m2 = date('n',strtotime($data['user']['Term']['account_end'])) ;
        $d2 = date('j',strtotime($data['user']['Term']['account_end'])) ;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $y2, 33.5, 74);
          $this->_putNumberTableItem($pdf, $m2, 43.7, 74);
          $this->_putNumberTableItem($pdf, $d2, 53.9, 74);
        } else {
          $this->_putNumberTableItem($pdf, $y2, 31, 74 + $height2016-0.2);
          $this->_putNumberTableItem($pdf, $m2, 41.2, 74 + $height2016-0.2);
          $this->_putNumberTableItem($pdf, $d2, 51.4, 74 + $height2016-0.2);
        }

        //本社 row left 10
        $office_name = h('本社');
        $pdf->SetFont($font, null, 8, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(34, 213.0);
        } else {
          $pdf->SetXY(30, 214);
        }
        $pdf->MultiCell(20, 5, $office_name, 0, 'C');

        //Namelist.prefecture + city + address row left 11
        $pdf->SetFont($font, null, 6, true);
        $name_list = h($data['user']['NameList']['city']) .  h($data['user']['NameList']['address']);
        $name_list = mb_substr($name_list, 0, 32, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(60.9, 213.4);
        } else {
          $pdf->SetXY(59, 214.4);
        }
        $pdf->MultiCell($point_start_x - 2, 5, $name_list, 0, 'C');

        //confirm_date row left 12
        $pdf->SetFont($font, null, 7, true);
        $confirm_date = h($data['confirm_date']);
        $y = date('Y',strtotime($data['confirm_date'])) -1988;
        $m = date('n',strtotime($data['confirm_date'])) ;
        $d = date('j',strtotime($data['confirm_date'])) ;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(118, 230);
          $pdf->MultiCell(28, 5, $y, 0, 'R');
          $pdf->SetXY(126.2, 230);
          $pdf->MultiCell(28, 5, $m, 0, 'R');
          $pdf->SetXY(135.8, 230);
          $pdf->MultiCell(28, 5, $d, 0, 'R');
        } else {
          $pdf->SetXY(116, 230);
          $pdf->MultiCell(28, 5, $y, 0, 'R');
          $pdf->SetXY(125.7, 230);
          $pdf->MultiCell(28, 5, $m, 0, 'R');
          $pdf->SetXY(135.3, 230);
          $pdf->MultiCell(28, 5, $d, 0, 'R');
        }

        //法人税の期末資本金等
        $houjinzei_shihonkintou = number_format($data['houjinzei_shihonkintou']);
      //  $houjinzei_shihonkintou = mb_substr($houjinzei_shihonkintou, 0, 9, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(142.4, 244+1.5);
        } else {
          $pdf->SetXY(142.4+ $adjust2016right, 244 +1.5);
        }
        $pdf->MultiCell(20, 5, $houjinzei_shihonkintou, 0, 'R');

        //return_class row left 13
        $return_class = $data['user']['User']['return_class'];
        if($return_class == '1') {
            $x = 177.6;
        } else if ($return_class == '2') {
            $x = 186;
        }
        $y = !empty($return_class) ? 230 : 228.5;
        $_font = !empty($return_class) ? 16 : 22;
        $pdf->SetFont($font, null, $_font, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($x, $y);
        } else {
          $pdf->SetXY($x-0.8, $y);
        }
        $pdf->MultiCell(20, 5, '◯', 0, 'C');

        //chukan_youhi row left 14
        $chukan_youhi = $data['chukan_youhi'];
        $x = !empty($chukan_youhi) ? 179.3 : 185.3;
        $pdf->SetFont($font, null, 12, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($x, 241.1);
        } else {
          $pdf->SetXY($x-0.7, 241.1);
        }
        $pdf->MultiCell(20, 5, '◯', 0, 'C');

        //extension_houjinzei row left 15
        $extension_houjinzei = isset($data['extension_houjinzei']) ? $data['extension_houjinzei'] : null;
        $x = (!empty($extension_jigyouz) &&  $extension_jigyouz != '無') ? 179.3 : 185.3;
        $pdf->SetFont($font, null, 12, true);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($x, 251.2);
        } else {
          $pdf->SetXY($x-1, 250.8);
        }
        $pdf->MultiCell(20, 5, '◯', 0, 'C');

        //User.business row right 1
        $business = h($data['user']['User']['business']);
        $pdf->SetFont($font, null, 8.2, true);
        $business = mb_substr($business, 0, 32, "utf-8");
        $height = (mb_strwidth($business, 'utf8') <= 32) ? 43.4 : 41.8;
        $align  = (mb_strwidth($business, 'utf8') <= 32) ? 'C' : 'L';
        $x      = 149.8;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY($x, $height);
        } else {
          $pdf->SetXY($x, $height + $height2016);
        }
        $pdf->MultiCell(49, 5, $business, 0, $align);

        //capital row right 2
        $pdf->SetFont($font, 'B', 7, true);
        $capital = $data['capital'];
        $x_right = 164;
        $y_right = 55.7;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $capital, $x_right, $y_right + 1);
        } else {
          $this->_putNumberTableItem($pdf, $capital, $x_right -0.7, $y_right + 1 +4.5);
        }


        //capital_sum row right 3
        $capital_sum = $data['capital_sum'];
        $y_right += 6.2;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $capital_sum, $x_right, $y_right + 1.2);
        } else {
          $this->_putNumberTableItem($pdf, $capital_sum, $x_right -0.7 , $y_right + 5.5);
        }
        //houjinzei_shihonkintou row right 4
        $houjinzei_shihonkintou = $data['houjinzei_shihonkintou'];
        $y_right += 6.2;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $houjinzei_shihonkintou, $x_right, $y_right + 1.2);
        } else {
          $this->_putNumberTableItem($pdf, $houjinzei_shihonkintou, $x_right -0.7 , $y_right +5.5);
        }

        //確定 TODO: confirm right 5
        $pdf->SetFont($font, 'B', 6.8, true);
        $y_right += 6;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(141, $y_right);
          $pdf->MultiCell(20, 5, '確定', 0, 'C');
          $pdf->SetXY(141, $y_right);
          $pdf->MultiCell(20, 5, '確定', 0, 'C');
          $pdf->SetFont($font, null, 7, true);
        } else {
          $pdf->SetXY(141 -3, $y_right+3.7);
          $pdf->MultiCell(20, 5, '確定', 0, 'C');
          $pdf->SetFont($font, null, 7, true);
        }

        //pre_base_houjinzei
        $pre_base_houjinzei = $data['pre_base_houjinzei'];
        $y_right += 15;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $pre_base_houjinzei, 123.5, $y_right);
        } else {
          $adjust2016right = -1.1;
          $this->_putNumberTableItem($pdf, $pre_base_houjinzei, 123.5 - 1.5, $y_right + 4);
        }

        //base_houjinzei
        $pdf->SetFont($font, 'B', 7.2, true);
        $base_houjinzei = $data['base_houjinzei'];
        $y_right += 6.1 * 5 + 0.2;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $base_houjinzei, 115.8, $y_right , null, $round_thou);
        } else {
          $this->_putNumberTableItem($pdf, $base_houjinzei, 115.8+ $adjust2016right - 0.4, $y_right -3.5 , null, $round_thou);
        }
        $pdf->SetFont($font, 'B', 6.8, true);

        //houjinzeiwari_rate
        $houjinzeiwari_rate = $data['houjinzeiwari_rate'];
        $houjinzeiwari_rate = mb_substr($houjinzeiwari_rate, 0, 5, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(154.3, $y_right);
        } else {
          $pdf->SetXY(154.3+ $adjust2016right, $y_right -3.5);
        }
        $pdf->MultiCell(20, 5, $houjinzeiwari_rate, 0, 'C');

        //houjinzeiwari
        $houjinzeiwari = $data['houjinzeiwari'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $houjinzeiwari, 164, $y_right + 0.5);
        } else {
          $this->_putNumberTableItem($pdf, $houjinzeiwari, 164+ $adjust2016right, $y_right -3);
        }

        //sashihiki_houjinzeiwari
        $sashihiki_houjinzeiwari = $data['sashihiki_houjinzeiwari'];

          $_x_right = 156.5;
          $y_right += 6.1 * 4;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $sashihiki_houjinzeiwari, $_x_right + $x_margin, $y_right+0.1, null, $round_hund);
        } else {
          $this->_putNumberTableItem($pdf, $sashihiki_houjinzeiwari, $_x_right + $x_margin+ $adjust2016right, $y_right+0.1 +2, null, $round_hund);
        }

        //middle_houjinzeiwari
        $middle_houjinzeiwari = $data['middle_houjinzeiwari'];
        $y_right += 6.1;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $middle_houjinzeiwari, $_x_right + $x_margin, $y_right+0.1, null, $round_hund);
        } else {
          $this->_putNumberTableItem($pdf, $middle_houjinzeiwari, $_x_right + $x_margin+ $adjust2016right, $y_right+1.9, null, $round_hund);
        }

        //real_houjinzeiwari
        $real_houjinzeiwari = $data['real_houjinzeiwari'];
        $y_right += 6.1 * 2;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $real_houjinzeiwari, $_x_right + $x_margin, $y_right, null, $round_hund);
        } else {
          $this->_putNumberTableItem($pdf, $real_houjinzeiwari, $_x_right + $x_margin+ $adjust2016right, $y_right +1.9, null, $round_hund);
        }

        //kintouwari
        $kintouwari = $data['kintouwari'];
        $y_right += 6.1;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $kintouwari, $_x_right + $x_margin, $y_right, null, $round_hund);
        } else {
          $this->_putNumberTableItem($pdf, $kintouwari, $_x_right + $x_margin+ $adjust2016right, $y_right +1.7, null, $round_hund);
        }

        //months
        $pdf->SetFont($font, 'B', 8, true);
        $months = $data['months'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $months, 98.2, $y_right);
        } else {
          $this->_putNumberTableItem($pdf, $months, 98.2+ $adjust2016right -0.3, $y_right  +1.2);
        }

        //kintouwari_base
        $kintouwari_base = number_format($data['kintouwari_base']);
        $kintouwari_base = mb_substr($kintouwari_base, 0, 9, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(135.4, $y_right - 0.4);
        } else {
          $pdf->SetXY(135.4+ $adjust2016right, $y_right - 0.4 +1.5);
        }
        $pdf->MultiCell(20, 5, $kintouwari_base, 0, 'C');

        //middle_kintouwari
        $pdf->SetFont($font, 'B', 6.8, true);
        $middle_kintouwari = $data['middle_kintouwari'];
        $y_right += 6.1;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $middle_kintouwari, $_x_right + $x_margin, $y_right, null, $round_hund);
        } else {
          $this->_putNumberTableItem($pdf, $middle_kintouwari, $_x_right + $x_margin+ $adjust2016right, $y_right +1.6, null, $round_hund);
        }

        //real_kintouwari
        $real_kintouwari = $data['real_kintouwari'];
        $y_right += 6.1;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $real_kintouwari, $_x_right + $x_margin, $y_right, null, $round_hund);
        } else {
          $this->_putNumberTableItem($pdf, $real_kintouwari, $_x_right + $x_margin+ $adjust2016right, $y_right +1.3, null, $round_hund);
        }

        //tax_sum
        $tax_sum = $data['tax_sum'];
        $y_right += 6.1 * 3;
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $tax_sum, $_x_right + $x_margin, $y_right - 6.1*2, null, $round_hund);
          $this->_putNumberTableItem($pdf, $tax_sum, $_x_right + $x_margin*3, $y_right, null);
        } else {
          $this->_putNumberTableItem($pdf, $tax_sum, $_x_right + $x_margin+ $adjust2016right, $y_right - 6.1*2 + 1.4, null, $round_hund);
          $this->_putNumberTableItem($pdf, $tax_sum, $_x_right + $x_margin*3+ $adjust2016right, $y_right +1.4, null);
        }

        //employee_num
        $employee_num = $data['employee_num'];
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $y_right += 13;
          $this->_putNumberTableItem($pdf, $employee_num, 164.0, $y_right + 1.7);
          $y_right += 11.7;
          $this->_putNumberTableItem($pdf, $employee_num, 164.0, $y_right + 0.5);
        } else {
          $y_right += 13;
          $this->_putNumberTableItem($pdf, $employee_num, 164.0+ $adjust2016right, $y_right + 2.5);
          $y_right += 11.7;
          $this->_putNumberTableItem($pdf, $employee_num, 164.0+ $adjust2016right, $y_right + 1.1);
        }

        //kanpu_message
        $pdf->SetFont($font, 'B', 8, true);
        $kanpu_message = isset($data['kanpu_message']) ? h($data['kanpu_message']) : null;
        $kanpu_len     = mb_strlen($kanpu_message, 'utf-8');
        $kanpu_message = $this->roundLineStrByWidth($kanpu_message, 2, $kanpu_len);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(185, 220);
        } else {
          $pdf->SetXY(185+ $adjust2016right, 220 + $adjust2016up);
        }
        $pdf->MultiCell(20, 5, $kanpu_message, 0, 'R');

        //account_number
        $pdf->SetFont($font, 'B', 7, true);
        $account_number = $data['account_number'];
        $account_number = mb_substr($account_number, 0, 7, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(160, 266.5);
        } else {
          $pdf->SetXY(160+ $adjust2016right, 266.5 -0.5);
        }
        $pdf->MultiCell(28, 5, $account_number, 0, 'C');

        //chukan_kanpu
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetFont($font, 'B', 7, true);
        $chukan_kanpu = isset($data['chukan_kanpu']) ? $data['chukan_kanpu'] : '';
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $this->_putNumberTableItem($pdf, $chukan_kanpu, 164, 272.4);
        } else {
          $this->_putNumberTableItem($pdf, $chukan_kanpu, 163.5, 272.4);
        }

        //bank_name
        $pdf->SetFont($font, null, 7.5, true);
        $bank_name = $data['bank_name'];
        $bank_name = mb_substr($bank_name, 0, 20, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){

          $pdf->SetXY(135, 260.6);
        } else {
          $pdf->SetXY(134, 259.8);
        }
        $pdf->MultiCell(28, 5, $bank_name, 0, 'R');

        //banch_name
        $pdf->SetFont($font, null, 7.5, true);
        $branch_name = $data['branch_name'];
        $branch_name = mb_substr($branch_name, 0, 14, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(160, 260.6);
        } else {
          $pdf->SetXY(167, 259.8);
        }
        $pdf->MultiCell(23, 5, $branch_name, 0, 'R');

        //account_class
        $pdf->SetFont($font, null, 18, true);
        $account_class = $data['account_class'];
        if (!empty($account_class)) {
            $x = !empty($account_class) && ($account_class == '普通') ? 136.5 : 144;
            if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
              $pdf->SetXY($x, 264.3);
            } else {
              $pdf->SetXY($x-1.5, 263.5);
            }
            $pdf->MultiCell(28, 5, '◯', 0, 'C');
        }

        //TaxAccountant.phone_number
        $pdf->SetFont($font, null, 8, true);
        $phone_number = $data['TaxAccountant']['phone_number'];
        $phone_number = mb_substr($phone_number, 0, 13, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
          $pdf->SetXY(166.5, 287.8);
        } else {
          $pdf->SetXY(166.5, 287);
        }
        $pdf->MultiCell(28, 2, $phone_number, 0, 'C');


        return $pdf;
    }

    /**
     * 均等割額の計算に関する明細書
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    public function export_prefecture43 ($pdf, $font)
    {
        $template         = $this->setTemplateAddPage($pdf, $font, 'tokyo_return_6_4_3.pdf');

        $PrefectureReturn = ClassRegistry::init('PrefectureReturn');
        $data             = $PrefectureReturn->findFor6_43();

        //Set x y
        $x_right_row   = 153.4;
        $x_right_row_1 = 134.6;
        $x_right_row_2 = 129.3;
        $margin        = array(2.7, 2.7);
        $round_thou    = 100;

        //User.name
        $pdf->SetFont($font, null, 8, true);
        $user_name = $data['User']['name'];
        $user_name = mb_substr($user_name, 0, 32, "utf-8");
        $x_user_name = 148.3;
        $height = (mb_strwidth($user_name, 'utf8') <= 32) ? 23.8 : 22;
        $align  = (mb_strwidth($user_name, 'utf8') <= 32) ? 'C' : 'L';
        $pdf->SetXY($x_user_name, $height);
        $pdf->MultiCell(48, 5, $user_name, 0, $align);

        //Term.account_beggining
        $account_beggining = $data['Term']['account_beggining'];
        $height = 21.8;
        $account_beggining_x = 107;
        $date_margin = array(-4.8, -5.2, -6);
        $this->putHeiseiDate($pdf, $height, $account_beggining_x, $account_beggining, $date_margin, true);

        //Term.account_end
        $account_end = $data['Term']['account_end'];
        $height += 3.7;
        $this->putHeiseiDate($pdf, $height, $account_beggining_x, $account_end, $date_margin, true);

        //NameList.city
        $pdf->SetFont($font, null, 9, true);
        $city = h($data['NameList']['city']);
        $city = mb_substr($city, 0, 3, "utf-8");
        $pdf->SetXY(15, 51.4);
        $pdf->MultiCell(12, 2, $city, 0, 'C');

        //NameList.address
        $address = h($data['NameList']['address']);
        $height  = (mb_strwidth($address, 'utf8') <= 24) ? 51.5 : 48.5;
        $align   = (mb_strwidth($address, 'utf8') <= 24) ? 'C' : 'L';
        $address = mb_substr($address, 0, 24, "utf-8");
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(30.8, $height);
        $pdf->MultiCell(36, 2, $address, 0, $align);

        //months
        $pdf->SetFont($font, null, 9, true);
        $months = $data['months'];
        $pdf->SetXY(63, 59.4);
        $pdf->MultiCell(28, 2, $months, 0, 'C');

        //employee_num
        $employee_num = $data['employee_num'];
        $pdf->SetXY(82, 59.4);
        $pdf->MultiCell(28, 2, $employee_num, 0, 'C');

        //employee_num 合計数
        $employee_num = $data['employee_num'];
        $this->_putNumberTableItem($pdf, $employee_num, 70.2,263, $margin);

        //over_rate
        $pdf->SetFont($font, 'B', 8.2, true);
        $over_rate = isset($data['over_rate']) ? $data['over_rate'] : null;
        $this->_putNumberTableItem($pdf, $over_rate, $x_right_row_2, 142.8, $margin);

        //over_month
        $over_month = isset($data['over_month']) ? $data['over_month'] : null;
        $this->_putNumberTableItem($pdf, $over_month, $x_right_row_1, 142.8, array(2.6, 2.6));

        //over_tax
        $over_tax = isset($data['over_tax']) ? $data['over_tax'] : null;
        $this->_putNumberTableItem($pdf, $over_tax, $x_right_row + 2.7, 142.7, $margin, $round_thou);

        //under_rate
        $under_rate = isset($data['under_rate']) ? $data['under_rate'] : null;
        $this->_putNumberTableItem($pdf, $under_rate, $x_right_row_2, 157.8, $margin);

        //under_month
        $under_month = isset($data['under_month']) ? $data['under_month'] : null;
        $this->_putNumberTableItem($pdf, $under_month, $x_right_row_1, 157.8, array(2.6, 2.6));

        //under_tax
        $under_tax = isset($data['under_tax']) ? $data['under_tax'] : null;
        $this->_putNumberTableItem($pdf, $under_tax, $x_right_row + 2.7, 157.7, $margin, $round_thou);

        //tax_sum
        $tax_sum = isset($data['tax_sum']) ? $data['tax_sum'] : null;
        $this->_putNumberTableItem($pdf, $tax_sum, $x_right_row + 2.7, 247.7, $margin, $round_thou);

        return $pdf;
    }

    /**
     * 東京都六号様式
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_tokyo_return($pdf, $font) {


        $Schedules14      = ClassRegistry::init('Schedules14');
        $Schedules7       = ClassRegistry::init('Schedules7');
        $PrefectureReturn = ClassRegistry::init('PrefectureReturn');
        $Schedules4       = ClassRegistry::init('Schedules4');
        $FixedAsset       = ClassRegistry::init('FixedAsset');
        $Schedules168      = ClassRegistry::init('Schedules168');

        //事業年度で様式選択
        $term_info = $Schedules14->getCurrentTerm();
        // $target_day = '2016/04/30';
        // if(strtotime($target_day) > strtotime($term_info['Term']['account_end'])){
        //   $template = $this->setTemplateAddPage($pdf, $font, 'tokyo_return06.pdf');
        // } else {
        //   $template = $this->setTemplateAddPage($pdf, $font, 'tokyo_return06_h28.pdf');
        // }

        $target_day = '2016/04/30';
        $target_day2804 = '2016/04/01';
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $template = $this->setTemplateAddPage($pdf, $font, 'sH280401tokyo_local_6.pdf');
        } else if(strtotime($target_day) < strtotime($term_info['Term']['account_end'])) {
          $template = $this->setTemplateAddPage($pdf, $font, 'tokyo_return06_h28.pdf');
        } else {
          $template = $this->setTemplateAddPage($pdf, $font, 'tokyo_return06.pdf');
        }

        //提出先
        $account_info = $PrefectureReturn->getAccountInfo();
        $pdf->SetFont($font, null, 7, true);
        $pdf->SetXY(38.3, 16);
        $pdf->MultiCell(16, 5, h($account_info['AccountInfo']['office_tokyo']), 0, 'C');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        //欠損金当期控除額の値を取得
        $data7 = $Schedules7->findForIndex7($preSum,$data14['not_cost']);

        //申告書に表示するデータ
        $data = $PrefectureReturn->findFor6($preSum,$data14['not_cost'],$data7['this_deduction_sum']);

        $point_start_x = 83;       // 出力開始位置起点(縦)
        $point_start_y = 33.1;     // 出力開始位置起点(縦)
        $point_step    = 12.7;     // 次の出力
        $x_left_row    = 40.7;
        $x_step_row    = 6.8;
        $margin        = array(3.2, 3.2, 2.5);
        $x_margin      = 3.2;
        $round_hund    = 100;
        $round_thou    = 1000;
        $y_left_row_start = 76.5;

        //法人番号
        $pdf->SetFont($font, null, 7.1, true);
        $company_number = ($data['user']['User']['company_number']);
        $x_right = 70;
        $this->_putNumberTableItem($pdf, $company_number, $x_right + $x_margin*3, 17, $margin);

        //phone number row left 1
        $pdf->SetFont($font, null, 8, true);
        $phone_number = h($data['user']['NameList']['phone_number']);
        $phone_number = mb_substr($phone_number, 0, 13, "utf-8");
        $pdf->SetXY($point_start_x, $point_start_y);
        $pdf->MultiCell(28, 5, $phone_number, 0, 'C');

        //Namelist.prefecture + city + address row left 2
        $pdf->SetFont($font, null, 9.1, true);
        $name_list = h($data['user']['NameList']['prefecture']) . h($data['user']['NameList']['city']) .  h($data['user']['NameList']['address']);
        $height    = (mb_strwidth($name_list, 'utf8') <= 60) ? 26.5 : 23.5;
        $align     = (mb_strwidth($name_list, 'utf8') <= 60) ? 'C' : 'L';
        $name_list = mb_substr($name_list, 0, 60, "utf-8");
        $pdf->SetXY(22.6, $height);
        $pdf->MultiCell(99, 5, $name_list, 0, $align);

        //user.NameList.name_furigana row left 3
        $pdf->SetFont($font, null, 5.2, true);
        $name_furigana = h($data['user']['NameList']['name_furigana']);
        $name_furigana = mb_substr($name_furigana, 0, 52, "utf-8");
        $pdf->SetXY(22.6, 37.0);
        $pdf->MultiCell(99, 5, $name_furigana, 0, 'C');
        $pdf->SetFont($font, null, 7, true);

        //user.NameList.name row left
        $name = isset($data['user']['NameList']['name']) ? h($data['user']['NameList']['name']) : null;
        $name = mb_substr($name, 0, 38, "utf-8");
        $pdf->SetXY(22.0, 41.5);
        $pdf->MultiCell(99, 5, $name, 0, 'C');

        //Term.account_beggining row left 4
        $y1 = date('Y',strtotime($data['user']['Term']['account_beggining'])) -1988;
        $m1 = date('n',strtotime($data['user']['Term']['account_beggining'])) ;
        $d1 = date('j',strtotime($data['user']['Term']['account_beggining'])) ;
        $pdf->SetFont($font, null, 9, true);
        $marginDate = array(4, 4.9);
        $year  = substr($y1, 0, 1);
        $pdf->SetXY(15, 59);
        $pdf->MultiCell(24, 5, $year, 0, 'L');
        $this->_putNumberTableItem($pdf, $y1, -9.5, 59, $marginDate);
        $this->_putNumberTableItem($pdf, $m1, 5.6, 59, $marginDate);
        $this->_putNumberTableItem($pdf, $d1, 20.7, 59, $marginDate);

        //Term.account_end row left 5
        $y2 = date('Y',strtotime($data['user']['Term']['account_end'])) -1988;
        $m2 = date('n',strtotime($data['user']['Term']['account_end'])) ;
        $d2 = date('j',strtotime($data['user']['Term']['account_end'])) ;
        $this->_putNumberTableItem($pdf, $y2, 43.7, 59, $marginDate);
        $this->_putNumberTableItem($pdf, $m2, 59.0, 59, $marginDate);
        $this->_putNumberTableItem($pdf, $d2, 74.0, 59, $marginDate);

        //shotoku_sum row left 6
        $pdf->SetFont($font, 'B', 7.1, true);
        $pdf->SetTextColor(6, 6, 6);
        $shotoku_sum = $data['shotoku_sum'];
        $x_left_row = 32.2;
        $height = 71.6;
        $this->_putNumberTableItem($pdf, $shotoku_sum, $x_left_row + $x_margin*3, $height, $margin);

        //under400Base row left 7
        $under400Base = (int)($data['under400Base']);
        $this->_putNumberTableItem($pdf, $under400Base, $x_left_row, $y_left_row_start + 0.1, $margin, $round_thou);

        //over400Base row left 8
        $over400Base = (int)($data['over400Base']);
        $height = $y_left_row_start + 5.6;
        $this->_putNumberTableItem($pdf, $over400Base, $x_left_row, $height, $margin, $round_thou);

        //over800Base row left 9
        $height += 5.6;
        $over800Base = (int)($data['over800Base']);
        $this->_putNumberTableItem($pdf, $over800Base, $x_left_row, $height, $margin, $round_thou);

        //base_sum row left 9
        $height += 5.6;
        $base_sum = (int)($data['base_sum']);
        $this->_putNumberTableItem($pdf, $base_sum, $x_left_row, $height, $margin, $round_thou);

        //under400_rate row left 10
        $pdf->SetFont($font, null, 8, true);
        $under400_rate = h($data['under400_rate']);
        $pdf->SetXY(64.8, $y_left_row_start);
        $pdf->MultiCell(28, 5, $under400_rate, 0, 'C');

        //over400_rate row left 11
        $over400_rate = h($data['over400_rate']);
        $height2 = $y_left_row_start + 5.8;
        $pdf->SetXY(64.8, $height2);
        $pdf->MultiCell(28, 5, $over400_rate, 0, 'C');

        //over800_rate row left 12
        $over800_rate = h($data['over800_rate']);
        $height2 += 5.6;
        $pdf->SetXY(64.8, $height2);
        $pdf->MultiCell(28, 5, $over800_rate, 0, 'C');

        //middle_business_tax row left 13
        $pdf->SetFont($font, 'B', 7.1, true);
        $x_row_middle = 84.8;
        $middle_business_tax = (int)($data['middle_business_tax']);
        $y_adjust = 0;
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_adjust = 5.6;
        }
        $this->_putNumberTableItem($pdf, $middle_business_tax, $x_row_middle, 148.9 + $y_adjust, $margin, $round_hund, false);

        //real_business_tax row left 14
        $real_business_tax = (int)($data['real_business_tax']);
        $this->_putNumberTableItem($pdf, $real_business_tax, $x_row_middle, 154.5 + $y_adjust, $margin, $round_hund, false);
        $this->_putNumberTableItem($pdf, $real_business_tax, $x_left_row - 5.8, 160.0 + $y_adjust, $margin, $round_hund, false);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_adjust = 6.1;
        }
        $this->_putNumberTableItem($pdf, $real_business_tax, $x_row_middle + $x_margin*2, 170.7 + $y_adjust, $margin);
        //special_tax_rate row left 15
        $special_tax_rate = h($data['special_tax_rate']);
        $pdf->SetXY(64.8, 181.7 + $y_adjust);
        $pdf->MultiCell(28, 5, $special_tax_rate, 0, 'C');

        //business_tax
        $business_tax = (int)($data['business_tax']);
        $this->_putNumberTableItem($pdf, $business_tax, $x_left_row + $x_margin, 181.7  + $y_adjust, $margin, $round_hund, false);

        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
            $this->_putNumberTableItem($pdf, $business_tax, $x_left_row-5.8, 148.4  + $y_adjust, $margin, $round_hund, false);
          $y_adjust = 6.6;
        }
        //special_tax_sum row left 16
        $special_tax_sum = (int)($data['special_tax_sum']);
        $this->_putNumberTableItem($pdf, $special_tax_sum, $x_row_middle, 181.7 + $y_adjust, $margin, $round_hund, false);
        $this->_putNumberTableItem($pdf, $special_tax_sum, $x_row_middle, 192.0 + $y_adjust, $margin, $round_hund, false);
        //(56)
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $this->_putNumberTableItem($pdf, $special_tax_sum, $x_row_middle, 197.6 + $y_adjust, $margin, $round_hund, false);
        }

        //middle_special_tax row left 17
        $middle_special_tax = (int)($data['middle_special_tax']);
        $y2 = 197.3;
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $this->_putNumberTableItem($pdf, $middle_special_tax, $x_left_row - 5.8, 203 + $y_adjust, $margin, $round_hund, false);
        } else {
          $this->_putNumberTableItem($pdf, $middle_special_tax, $x_row_middle, $y2 + $y_adjust, $margin, $round_hund, false);
        }
        //real_special_tax row left 18
        $real_special_tax = h($data['real_special_tax']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          //(59)
          $y2 = $y2 + 11;
          $this->_putNumberTableItem($pdf, $real_special_tax, $x_left_row - 5.8, $y2 + $y_adjust + 0.2, $margin, $round_hund, false);
          //(61)
          $y2 = $y2 + 11;
          $this->_putNumberTableItem($pdf, $real_special_tax, ($x_left_row - 5.8) + $x_margin*2, $y2 -5.5 + $y_adjust, $margin);
        } else {
          $y2 = $y2 + 5.5;
          $this->_putNumberTableItem($pdf, $real_special_tax, $x_row_middle, $y2 + $y_adjust, $margin, $round_hund, false);
          $y2 = $y2 + 5.5;
          $this->_putNumberTableItem($pdf, $real_special_tax, $x_row_middle + $x_margin*2, $y2 + $y_adjust, $margin);
        }
        //pre_shotoku row left 19
        $pre_shotoku = h($data['pre_shotoku']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_adjust = 1.4;
        }
        $y2 = $y2 + 6.2;
        $this->_putNumberTableItemNon0($pdf, $pre_shotoku, $x_row_middle + $x_margin*2, $y2 + 0.2 + $y_adjust, $margin);

        //shotokuzei row left 20
        $shotokuzei = h($data['shotokuzei']);
        $y2 = $y2 + 5.2;
        $this->_putNumberTableItemNon0($pdf, $shotokuzei, $x_row_middle + $x_margin*2, $y2 + $y_adjust, $margin);

        //preSum row left 21
        $preSum = (int)($data['preSum']);
        $y2 = $y2 + 5.6 * 4;
        $this->_putNumberTableItemNon0($pdf, $preSum, $x_row_middle + $x_margin*2, $y2 + 0.2 + $y_adjust, $margin);

        //kessonkin_koujyo row left 22
        $kessonkin_koujyo = (int)($data['kessonkin_koujyo']);
        $y2 = $y2 + 5.6;
        $this->_putNumberTableItemNon0($pdf, $kessonkin_koujyo, $x_row_middle + $x_margin*2, $y2 + $y_adjust, $margin);

        //shotoku row left 23
        $shotoku = (int)($data['shotoku']['income']);
        $y2 = $y2 + 5.6;
        $this->_putNumberTableItem($pdf, $shotoku, $x_row_middle + $x_margin*2, $y2 + 0.7 + $y_adjust, $margin);

        //confirm_date row left 24
        $pdf->SetFont($font, null, 7, true);
        $y = date('Y',strtotime($data['confirm_date'])) -1988;
        $m = date('n',strtotime($data['confirm_date'])) ;
        $d = date('j',strtotime($data['confirm_date'])) ;
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y2 = $y2 -24.2;
          $pdf->SetXY(149, $y2);
          $pdf->MultiCell(28, 5, $y, 0, 'R');
          $pdf->SetXY(156.2, $y2);
          $pdf->MultiCell(28, 5, $m, 0, 'R');
          $pdf->SetXY(163.5, $y2);
          $pdf->MultiCell(28, 5, $d, 0, 'R');
        } else {
          $y2 = $y2 + 5.6;
          $pdf->SetXY(15.1, $y2);
          $pdf->MultiCell(28, 5, $y, 0, 'R');
          $pdf->SetXY(21.3, $y2);
          $pdf->MultiCell(28, 5, $m, 0, 'R');
          $pdf->SetXY(28.8, $y2);
          $pdf->MultiCell(28, 5, $d, 0, 'R');
        }

        // Right
        //User.business right row 1
        $business = h($data['user']['User']['business']);
        $business = mb_substr($business, 0, 14, "utf-8");
        $pdf->SetFont($font, null, 6, true);
        $pdf->SetXY(154.0, 21.2);
        $pdf->MultiCell(40, 2, $business, 0, 'C');

        //capital right row 2
        $pdf->SetFont($font, null, 7.1, true);
        $capital = h($data['capital']);
        $x_right = 159.1;
        $this->_putNumberTableItem($pdf, $capital, $x_right + $x_margin*3, 28.9, $margin);

        //capital_sum right row 3
        $capital_sum = h($data['capital_sum']);
        $y_right_start = 44.5;
        $this->_putNumberTableItem($pdf, $capital_sum, $x_right + $x_margin*3, $y_right_start - 1, $margin);

        //houjinzei_shihonkintou right row 4
        $houjinzei_shihonkintou = h($data['houjinzei_shihonkintou']);
        $y_right = $y_right_start;
        $this->_putNumberTableItem($pdf, $houjinzei_shihonkintou, $x_right + $x_margin*3, $y_right + 7.0, $margin);

        //確定 right row 5 TODO: don't know
        $pdf->SetFont($font, null, 5.6, true);
        $pdf->SetXY(143.0, 59.1);
        $pdf->MultiCell(28, 2, '確定', 0, 'C');
        $pdf->SetXY(143.0, 59.1);
        $pdf->MultiCell(28, 2, '確定', 0, 'C');

        //pre_base_houjinzei row 6
        $pdf->SetFont($font, null, 7.1, true);
        $y_right = $y_right_start + 5.5 * 5;
        $pre_base_houjinzei = (int)($data['pre_base_houjinzei']);
        if($pre_base_houjinzei != 0){
          $this->_putNumberTableItem($pdf, $pre_base_houjinzei, $x_right + $x_margin*3, $y_right - 0.2, $margin);
        } else {
          $this->_putNumberTableItem($pdf, 0, $x_right + $x_margin*3, $y_right - 0.2, $margin);
        }

        //under400 row 7
        $y_right += 5.5;
        $x_right2 = $x_row_middle;
        $under400 = isset($data['under400']) ? (int)($data['under400']) : null;
        $this->_putNumberTableItem($pdf, $under400, $x_right2, $y_right-0.4, $margin, $round_hund, false);

        //over400 row 8
        $over400 = isset($data['over400']) ? (int)($data['over400']) : null;
        $y_right += 4.6;
        $this->_putNumberTableItem($pdf, $over400, $x_right2, $y_right, $margin, $round_hund, false);

        //over800 row 9
        $over800 = isset($data['over800']) ? (int)($data['over800']) : null;
        $y_right += 5.6;
        $this->_putNumberTableItem($pdf, $over800, $x_right2, $y_right, $margin, $round_hund, false);

        //business_tax row 10
        $business_tax = isset($data['business_tax']) ? (int)($data['business_tax']) : null;
        $y_right += 5.6;
        $this->_putNumberTableItem($pdf, $business_tax, $x_right2, $y_right, $margin, $round_hund, false);
        $this->_putNumberTableItem($pdf, $business_tax, $x_right2, 137.7, $margin, $round_hund, false);
        //$this->_putNumberTableItem($pdf, $business_tax, $x_left_row + 0.5, 198.0, $margin);

        //base_houjinzei row 11
        $base_houjinzei = (int)($data['base_houjinzei']);
        if(strtotime($target_day) > strtotime($term_info['Term']['account_end'])){
          $y_right += 5.6;
        } else {
          $y_right += 0.1;
        }
        $this->_putNumberTableItem($pdf, $base_houjinzei, $x_right, $y_right, $margin, $round_thou);

        //base_houjinzei row 12
        $houjinzeiwari = (int)($data['houjinzeiwari']);
        $y_right += 5.6 * 2;
        $this->_putNumberTableItem($pdf, $houjinzeiwari, $x_right + $x_margin*3, $y_right, $margin);

        //houjinzeiwari_rate
        $pdf->SetFont($font, null, 3.8, true);
        $houjinzeiwari_rate = ($data['houjinzeiwari_rate']);
        $houjinzeiwari_rate = mb_substr($houjinzeiwari_rate, 0, 4, "utf-8");
        $pdf->SetXY($x_right - 21.0, $y_right + 1.0);
        $pdf->MultiCell(28, 5, $houjinzeiwari_rate, 0, 'C');

        //sashihiki_houjinzeiwari row 13
        $pdf->SetFont($font, null, 7.1, true);
        $sashihiki_houjinzeiwari = (int)($data['sashihiki_houjinzeiwari']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_right += 5.6 * 4;
        } else if(strtotime($target_day) < strtotime($term_info['Term']['account_end'])){
          $y_right += 5.6 * 5;
        } else {
          $y_right += 5.6 * 4;
        }
        $this->_putNumberTableItem($pdf, $sashihiki_houjinzeiwari, $x_right + $x_margin, $y_right - 0.3, $margin, $round_hund, false);

        //middle_houjinzeiwari row 14
        $middle_houjinzeiwari = (int)($data['middle_houjinzeiwari']);
        $y_right += 5.5 * 3;
        $this->_putNumberTableItem($pdf, $middle_houjinzeiwari, $x_right + $x_margin, $y_right-5.6*2, $margin, $round_hund, false);

        //real_houjinzeiwari row 15
        $real_houjinzeiwari = (int)($data['real_houjinzeiwari']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
        } else {
          $y_right += 5.4;
        }
        $this->_putNumberTableItem($pdf, $real_houjinzeiwari, $x_right + $x_margin, $y_right, $margin, $round_hund, false);

        //months row 16
        $months = h($data['months']);
        $y_right += 5.8;
        $this->_putNumberTableItem($pdf, $months, $x_right + 3.2, $y_right, $margin);

        //kintouwari row 16
        $kintouwari = (int)($data['kintouwari']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_right += 5.9;
        } else {
          $y_right += 5.8;
        }
        $this->_putNumberTableItem($pdf, $kintouwari, $x_right + $x_margin, $y_right, $margin, $round_hund, false);

        //kintouwari_base row 17
        $kintouwari_base = number_format($data['kintouwari_base']);
        $kintouwari_base = mb_substr($kintouwari_base, 0, 9, "utf-8");
        if(strtotime($target_day) > strtotime($term_info['Term']['account_end'])){
          $pdf->SetXY(125, $y_right - 0.2);
        } else {
          $pdf->SetXY(124.9, $y_right - 0.2);
        }
        $pdf->MultiCell(28, 5, $kintouwari_base, 0, 'C');

        //middle_kintouwari row 18
        $middle_kintouwari = (int)($data['middle_kintouwari']);
        $y_right += 5.4 * 2;
        $pdf->SetXY($x_right, $y_right);
        $this->_putNumberTableItem($pdf, $middle_kintouwari, $x_right + $x_margin, $y_right-5.8, $margin, $round_hund, false);

        //real_kintouwari row 19
        $real_kintouwari = (int)($data['real_kintouwari']);
        $y_right += 5.4;
        $this->_putNumberTableItem($pdf, $real_kintouwari, $x_right + $x_margin, $y_right-5.6, $margin, $round_hund, false);

        //real_prefecture_tax row 19
        $real_prefecture_tax = (int)($data['real_prefecture_tax']);
        $y_right += 5.4;
        $this->_putNumberTableItem($pdf, $real_prefecture_tax, $x_right + $x_margin, $y_right - 5.5, $margin, $round_hund, false);
        $y_right += 5.4;
        $this->_putNumberTableItem($pdf, $real_prefecture_tax, $x_right + $x_margin*3, $y_right, $margin);

        //chukan_kanpu row 20
        $chukan_kanpu = (int)($data['chukan_kanpu']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $this->_putNumberTableItem($pdf, $chukan_kanpu, $x_right + $x_margin*3, 212.5, $margin);
        } else {
          $this->_putNumberTableItem($pdf, $chukan_kanpu, $x_right + $x_margin*3, 255.2, $margin);
        }

        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_adjust = -48;
        }
        //branch_name row 21
        $pdf->SetFont($font, null, 6.5, true);
        $branch_name = h($data['branch_name']);
        $branch_name = mb_substr($branch_name, 0, 15, "utf-8");
        $pdf->SetXY($x_right, 265.3 + $y_adjust);
        $pdf->MultiCell(40, 2, $branch_name, 0, 'L');

        //bank_name row 22
        $pdf->SetAutoPageBreak(false, 0);
        $bank_name = h($data['bank_name']);
        $bank_name = mb_substr($bank_name, 0, 15, "utf-8");
        $pdf->SetXY($x_right, 268.3 + $y_adjust);
        $pdf->MultiCell(40, 2, $bank_name, 0, 'L');

        //account_class row 23
        $account_class = h($data['account_class']);
        $account_class = mb_substr($account_class, 0, 2, "utf-8");
        $pdf->SetXY($x_right - 0.5, 271.6 + $y_adjust);
        $pdf->MultiCell(16, 2, $account_class, 0, 'C');

        //account_number row 24
        $pdf->SetFont($font, null, 7.1, true);
        $account_number = h($data['account_number']);
        $account_number = mb_substr($account_number, 0, 7, "utf-8");
        $pdf->SetXY($x_right + 28.9, 271.5 + $y_adjust);
        $pdf->MultiCell(16, 2, $account_number, 0, 'C');

        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $y_adjust = -49.4;
        }
        //houjinzei_shihonkintou
        $houjinzei_shihonkintou = h($data['houjinzei_shihonkintou']);
        $y_right = $y_right_start;
        $this->_putNumberTableItem($pdf, $houjinzei_shihonkintou, $x_right + $x_margin*3, 278.6 + $y_adjust, $margin);

        //kakuteizeigaku right row 25
        $kakuteizeigaku = (int)($data['kakuteizeigaku']);
        $this->_putNumberTableItem($pdf, $kakuteizeigaku, $x_right + $x_margin*3, 283.6 + $y_adjust, $margin);

        //tax_accountant_phone row 20
        $pdf->SetFont($font, null, 8, true);
        $tax_accountant_phone = h($data['tax_accountant_phone']);
        $tax_accountant_phone = mb_substr($tax_accountant_phone, 0, 12, "utf-8");
        $pdf->SetXY($x_right - 66, 282.4);
        $pdf->MultiCell(28, 2, $tax_accountant_phone, 0, 'C');

        //extension_jigyouz
        $extension_jigyouz = isset($data['extension_jigyouzei']) ? $data['extension_jigyouzei'] : null;
        $pdf->SetFont($font, null, 12, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $x = (!empty($extension_jigyouz) &&  $extension_jigyouz != '無') ? 158.5 : 163.4;
          $pdf->SetXY($x, 255.7);
        } else {
          $x = (!empty($extension_jigyouz) &&  $extension_jigyouz != '無') ? 24.1 : 27.8;
          $pdf->SetXY($x, 268.0);
        }
        $pdf->MultiCell(28, 2, '◯', 0, 'C');

        //extension_houjinzei
        $extension_houjinzei = isset($data['extension_houjinzei']) ? $data['extension_houjinzei'] : null;
        $pdf->SetFont($font, null, 12, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $x = (!empty($extension_houjinzei) &&  $extension_houjinzei != '無') ? 180.6 : 185.6;
          $pdf->SetXY($x, 255.7);
        } else {
          $x = (!empty($extension_houjinzei) &&  $extension_houjinzei != '無') ? 39.4 : 43.1;
          $pdf->SetXY($x, 268.0);
        }
        $pdf->MultiCell(28, 2, '◯', 0, 'C');

        //return_class
        $return_class = h($data['user']['User']['return_class']);

        $pdf->SetFont($font, null, 9, true);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          if($return_class == '1') {
              $x_return_class = 161.9;
          } else if ($return_class == '2') {
              $x_return_class = 170.9;
          }
          $pdf->SetXY($x_return_class, 262);
        } else {
          if($return_class == '1') {
              $x_return_class = 86.3;
          } else if ($return_class == '2') {
              $x_return_class = 104.3;
          }
          $pdf->SetXY($x_return_class, 262.5 + 6.4);
        }
        $pdf->MultiCell(28, 5, '◯', 0, 'C');

        //chukan_youhi
        $pdf->SetFont($font, null, 12.8, true);
        $chukan_youhi = h($data['chukan_youhi']);
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $x_chukan = !empty($chukan_youhi) ? 164.5 : 169.6;
          $pdf->SetXY($x_chukan, 272);
        } else {
          $x_chukan = !empty($chukan_youhi) ? 72.2 : 76.4;
          $pdf->SetXY($x_chukan, 272.8);
        }

        $pdf->MultiCell(28, 2, '◯', 0, 'C');

        //kanpu_message
        if(strtotime($target_day2804) <= strtotime($term_info['Term']['account_beggining'])) {
          $kanpu_message = isset($data['kanpu_message']) ? h($data['kanpu_message']) : null;
          $pdf->SetFont($font, null, 9.5, true);
          $pdf->SetXY(110, 285);
          $pdf->MultiCell(80, 5, $kanpu_message, 0, 'R');
        }

        return $pdf;
    }

    /**
     * 一括償却資産の損金算入に関する明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules1608s($pdf, $font) {

      $Schedules168 = ClassRegistry::init('Schedules168');

      //事業年度で様式選択
      $term_info = $Schedules168->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules16_8_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules16_08.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules16_8.pdf');
      }


        $schedules168s = $Schedules168->findPdfExportData();

        // エラー処理
        if ($schedules168s === FALSE) {
            $this->Controller->Session->setFlash('一括償却資産の損金算入に関する明細書データ更新中にエラーが発生しました。','/Flash/failure');
            $this->Controller->redirect(array());
            return;
        }

        $Term = ClassRegistry::init('Term');
        $term = $Term->getTermBegginingEnd();

        $term_id = CakeSession::read('Auth.User.term_id');

        // 今期のデータ
        $currentSchedules168 = Hash::extract($schedules168s, "{n}[term_id_for168={$term_id}]")[0];
        $schedules168s       = Hash::remove($schedules168s, "{n}[term_id_for168={$term_id}]");

        // 1ページ出力分ごとに分割
        $chunkSchedules168s = array_chunk($schedules168s, Configure::read('SCHEDULES1608_PDF_ROW'));
        $end_page = count($chunkSchedules168s) ? count($chunkSchedules168s) - 1 : 0;

        for ($page=0; $page <= $end_page; $page++) {
            $page_data = isset($chunkSchedules168s[$page]) ? $chunkSchedules168s[$page] : array();

            // ヘッダー出力
            // 法人名
            $pdf->SetFont($font, null, 9, true);
            $user_name = CakeSession::read('Auth.User.name');
            $user_name = substr($user_name,0,84);
            $height = (mb_strwidth($user_name, 'utf8') <= 28) ? 14.8 : 12.1;
            $user_name = $this->roundLineStrByWidth($user_name, 28, 2);
            $pdf->SetXY(152.2, $height);
            $pdf->MultiCell(47, 5, $user_name, 0, 'L');

            // 事業年度
            $pdf->SetFont($font, null, 10, true);
            $account_beggining = $term['Term']['account_beggining'];
            $account_end       = $term['Term']['account_end'];
            $this->putHeiseiDate($pdf, 11.5, 118, $account_beggining, array(0,-1,-2));
            $this->putHeiseiDate($pdf, 17.5, 118, $account_end, array(0,-1,-2));

            $step_x    = 19;   // 次の横方向
            $point_x   = 159;  // 横方向
            $point_y   = 29;   // 縦方向

            if ($page == 0) {
                // 当期データ
                $point_x += $step_x;
                $pdf->SetXY($point_x, $point_y);
                $this->_putSchedules168PDFColumn($pdf, $font, $currentSchedules168, true);
                $point_x -= $step_x;
            }

            foreach ($page_data as $data) {
                $pdf->SetXY($point_x, $point_y);
                $this->_putSchedules168PDFColumn($pdf, $font, $data);
                $point_x -= $step_x;
            }

            if ($page < $end_page) {
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
            }
        }

        return $pdf;
    }

    /**
     *
     * 一括償却資産の損金算入に関する明細書出力(列)
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $schedules168
     * @param bool isCurrentSchedule  当期データフラグ
     */
    function _putSchedules168PDFColumn(&$pdf, $font, $schedules168, $isCurrentSchedule=false) {

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $step_y = 16;

        if (!$isCurrentSchedule) {
            // 1.事業年度
            $pdf->SetFont($font, null, 9, true);
            $this->putHeiseiDate($pdf, $y - 2.8, $x, $schedules168['account_beggining'], array(2.5,-0.3,-2));
            $this->putHeiseiDate($pdf, $y + 3.5, $x, $schedules168['account_end'], array(2.5,-0.3,-2));
        }
        $y += $step_y + 1;

        // 2.取得価格合計額
        $pdf->SetFont($font, null, 7, true);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['total_cost']), 0, 'R');
        $y += $step_y;

        // 3.当期の月数
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, $schedules168['month_num'], 0, 'R');
        $y += $step_y;

        // 4.損金算入限度額
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['total_depreciation_sum']), 0, 'R');
        $y += $step_y;

        // 5.当期損金経理額
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['accounting_depreciation_sum']), 0, 'R');
        $y += $step_y;

        // 6.損金算入不足額
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['shortfall']), 0, 'R');
        $y += $step_y;

        // 7.損金算入限度超過額
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['excess_depreciation_sum']), 0, 'R');
        $y += $step_y;

        // 8.前期からの繰越
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['previous_excess_sum']), 0, 'R');
        $y += $step_y;

        // 9.当期損金認容額
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['tolerated']), 0, 'R');
        $y += $step_y;

        // 10.翌期への繰越額
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(18.5, 5, number_format($schedules168['next_excess_sum']), 0, 'R');

    }

    /**
     * 所得の金額の計算に関する明細書
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules4s($pdf, $font){

      $Schedules4 = ClassRegistry::init('Schedules4');

      //事業年度で様式選択
      $term_info = $Schedules4->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules4_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules4.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules4.pdf');
      }


        $Schedules7         = ClassRegistry::init('Schedules7');
        $Schedules14        = ClassRegistry::init('Schedules14');
        $IncomeTaxesPayable = ClassRegistry::init('IncomeTaxesPayable');
        $FixedAsset         = ClassRegistry::init('FixedAsset');
        $Term               = ClassRegistry::init('Term');
        $Schedules168       = ClassRegistry::init('Schedules168');


        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'],$data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        //欠損金当期控除額の値を取得
        $data7 = $Schedules7->findForIndex7($preSum,$data14['not_cost']);

        //一旦所得を計算する（税金を計算するため）
        $pre_datas = $Schedules4->calPreIncome($preSum,$data14['not_cost'],$data7['this_deduction_sum']);

        //当期の税金を算出
        $datas['tax'] = $IncomeTaxesPayable->findFor52tax($preSum,$data14['not_cost'],$data7['this_deduction_sum']);

        $datas['main'] = $Schedules4->findForIndex4($datas['tax']['kakutei_houjinzei'],$datas['tax']['kakutei_prefecture'],$datas['tax']['kakutei_city'],$datas['tax']['kakutei_jigyouzei'],$data14['not_cost'],$data7['this_deduction_sum'],$data16['plus'],$data16['minus']);
//        debug($datas); exit();

        $this->putDataSchedules4s($pdf, $font, $datas);

        $data_sub_plus     = $datas['main']['sub']['plus'];
        $data_sub_minus    = $datas['main']['sub']['minus'];

        $this->addPageSchedules4($pdf, $font, $template, $data_sub_plus, $data_sub_minus, $datas, true);

        return $pdf;
    }

    /**
     * 利益積立金額及び資本金等の額の計算に関する明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules0501s($pdf, $font) {

      $Schedules51 = ClassRegistry::init('Schedules51');

      //事業年度で様式選択
      $term_info = $Schedules51->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules5_1_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules5_1.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules5_1.pdf');
      }

        $FixedAsset = ClassRegistry::init('FixedAsset');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $TaxCalculation = ClassRegistry::init('TaxCalculation');
        $IncomeTaxesPayable = ClassRegistry::init('IncomeTaxesPayable');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7 = ClassRegistry::init('Schedules7');
        $Schedules168 = ClassRegistry::init('Schedules168');
        $CalculationDetail = ClassRegistry::init('CalculationDetail');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //一覧データ取得
        $datas = $Schedules51->findFor51($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $data7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);

        $datas['income'] = $IncomeTaxesPayable->findFor52tax($preSum, $data14['not_cost'], $data7['this_deduction_sum']);

        $prefecture_sum= $datas['income']['kakutei_prefecture'] + $datas['income']['kakutei_jigyouzei'];
        $datas['tax'] = $TaxCalculation->findForIndexNext($datas['income']['kakutei_houjinzei'],$datas['income']['kakutei_prefecture'],$datas['income']['kakutei_city'],$datas['income']['kakutei_jigyouzei']);

        //縦の合計算出
        $datas['all_beggining_sum'] = $datas['tax']['cal']['beggining_tax'] + $datas['cal']['beggining'] + $datas['income']['IncomeTaxesPayable']['beggining_sum'];
        $datas['all_increase_sum'] = $datas['tax']['cal']['increase_tax'] + $datas['cal']['increase'] + $datas['income']['sum_cost'];
        $datas['all_decrease_sum'] = $datas['tax']['cal']['decrease_tax'] + $datas['cal']['decrease'] + $datas['income']['decrease_sum'];
        $datas['all_end_sum'] = $datas['tax']['cal']['end_tax'] + $datas['cal']['end'] + $datas['income']['end_sum'];

        $this->_putDataLoopSchedules0501s($pdf, $font, $datas);
        $this->_putDataSchedules0501s($pdf, $font, $datas);

        $record_count = 0;
        $margin_line_top = 0;

        foreach ($datas['detail'] as $item) {
            $record_count++;
            if ($record_count < 24) {
                $this->_putDataDetailSchedules0501s($pdf, $font, $item, $margin_line_top);
            } else {
                $record_count = 0;
                $margin_line_top = 0;
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $this->_putDataDetailSchedules0501s($pdf, $font, $item, $margin_line_top);
                $this->_putDataLoopSchedules0501s($pdf, $font, $datas);
            }
            $margin_line_top += 5.5;
        }
      if($datas['depreciation']['previous_sum']>0 || $datas['depreciation']['minus_sum']>0 || $datas['depreciation']['plus_sum']>0 || $datas['depreciation']['next']>0){
        $record_count++;
        if ($record_count < 24) {
            $this->_putDataDepreciationSchedules0501s($pdf, $font, $datas, $margin_line_top);
        } else {
            $record_count = 0;
            $margin_line_top = 0;
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->_putDataDepreciationSchedules0501s($pdf, $font, $datas, $margin_line_top);
            $this->_putDataLoopSchedules0501s($pdf, $font, $datas);
        }
        $margin_line_top += 5.5;
      }

        foreach ($datas['sub'] as $item) {
            $record_count++;
            if ($record_count < 24) {
                $this->_putDataSub51Schedules0501s($pdf, $font, $item, $margin_line_top);
            } else {
                $record_count = 0;
                $margin_line_top = 0;
                $pdf->AddPage();
                $pdf->useTemplate($template, null, null, null, null, true);
                $this->_putDataSub51Schedules0501s($pdf, $font, $item, $margin_line_top);
                $this->_putDataLoopSchedules0501s($pdf, $font, $datas);
            }
            $margin_line_top += 5.5;
        }

        return $pdf;
    }

    /**
     * Put detail data in export Schedules0501s
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $item
     * @param int $margin_line_top
     */
    function _putDataDetailSchedules0501s(&$pdf, $font, $item, $margin_line_top) {
        $x_col1 = 97;
        $x_col2 = 127;
        $x_col3 = 157;
        $x_col4 = 187;

        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(37, 59.4 + $margin_line_top);
        if(!empty($item['CalculationDetail']['item_name_for5'])){
          $pdf->MultiCell(36, 5, $item['CalculationDetail']['item_name_for5'], 0, 'C');
        } else if(!empty($item['CalculationDetail']['item_name'])){
          $pdf->MultiCell(36, 5, $item['CalculationDetail']['item_name'], 0, 'C');
        }

        if (!empty($item['CalculationDetail']['account_beggining_sum']) &&
                strlen(number_format($item['CalculationDetail']['account_beggining_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['CalculationDetail']['account_beggining_sum'], $x_col1, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($item['CalculationDetail']['subtraction']) &&
                strlen(number_format($item['CalculationDetail']['subtraction'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['CalculationDetail']['subtraction'], $x_col2, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($item['CalculationDetail']['adding']) &&
                strlen(number_format($item['CalculationDetail']['adding'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['CalculationDetail']['adding'], $x_col3, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($item['next_sum']) &&
                strlen(number_format($item['next_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['next_sum'], $x_col4, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }
    }

    /**
     * Put depreciation data in export Schedules0501s
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $datas
     * @param int $margin_line_top
     */
    function _putDataDepreciationSchedules0501s(&$pdf, $font, $datas, $margin_line_top) {
        $x_col1 = 97;
        $x_col2 = 127;
        $x_col3 = 157;
        $x_col4 = 187;

        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(42, 59.4 + $margin_line_top);
        $pdf->MultiCell(28, 5, "減価償却超過額", 0, 'C');

        if (!empty($datas['depreciation']['previous_sum']) &&
                strlen(number_format($datas['depreciation']['previous_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['depreciation']['previous_sum'], $x_col1, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['depreciation']['minus_sum']) &&
                strlen(number_format($datas['depreciation']['minus_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['depreciation']['minus_sum'], $x_col2, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['depreciation']['plus_sum']) &&
                strlen(number_format($datas['depreciation']['plus_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['depreciation']['plus_sum'], $x_col3, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['depreciation']['next']) &&
                strlen(number_format($datas['depreciation']['next'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['depreciation']['next'], $x_col4, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }
    }

    /**
     * Put Sub51 data in export Schedules0501s
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $item
     * @param int $margin_line_top
     */
    function _putDataSub51Schedules0501s(&$pdf, $font, $item, $margin_line_top) {
        $x_col1 = 97;
        $x_col2 = 127;
        $x_col3 = 157;
        $x_col4 = 187;

        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(37, 59.4 + $margin_line_top);
        $pdf->MultiCell(36, 5, $item['Sub51']['item_name'], 0, 'C');

        if (!empty($item['Sub51']['account_beggining_sum']) &&
                strlen(number_format($item['Sub51']['account_beggining_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['Sub51']['account_beggining_sum'], $x_col1, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($item['Sub51']['subtraction_sum']) &&
                strlen(number_format($item['Sub51']['subtraction_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['Sub51']['subtraction_sum'], $x_col2, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($item['Sub51']['adding_sum']) &&
                strlen(number_format($item['Sub51']['adding_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['Sub51']['adding_sum'], $x_col3, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($item['next_sum']) &&
                strlen(number_format($item['next_sum'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $item['next_sum'], $x_col4, 59.4 + $margin_line_top, 10, 5, 'R', 9);
        }
    }

    /**
     * Put not loop data in export Schedules0501s
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $datas
     */
    function _putDataSchedules0501s(&$pdf, $font, $datas) {
        $pdf->SetTextColor(60, 60, 60);
        $x_col1 = 97;
        $x_col2 = 127;
        $x_col3 = 157;
        $x_col4 = 187;

        //Line 1
        if (isset($datas['main']['beggining_riekijyunbikin']) && !empty($datas['main']['beggining_riekijyunbikin']) &&
                strlen(number_format($datas['main']['beggining_riekijyunbikin'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['main']['beggining_riekijyunbikin'], $x_col1, 48.5, 10, 5, 'R', 9);
        }

        if (isset($datas['main']['Schedules51']['riekijyunbikin_decrease']) &&
                !empty($datas['main']['Schedules51']['riekijyunbikin_decrease']) &&
                strlen(number_format($datas['main']['Schedules51']['riekijyunbikin_decrease'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['main']['Schedules51']['riekijyunbikin_decrease'], $x_col2, 48.5, 10, 5, 'R', 9);
        }

        if (isset($datas['main']['Schedules51']['riekijyunbikin_increase']) &&
                !empty($datas['main']['Schedules51']['riekijyunbikin_increase']) &&
                strlen(number_format($datas['main']['Schedules51']['riekijyunbikin_increase'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['main']['Schedules51']['riekijyunbikin_increase'], $x_col3, 48.5, 10, 5, 'R', 9);
        }

        if (isset($datas['end_riekijyunbikin_cal']) && !empty($datas['end_riekijyunbikin_cal']) &&
                strlen(number_format($datas['end_riekijyunbikin_cal'])) < 14) {
            $this->_putBaseNumber($pdf, $font, $datas['end_riekijyunbikin_cal'], $x_col4, 48.4, 10, 5, 'R', 9);
        }

        $margin_line_top = 44;

        //Line 26
        if (!empty($datas['tax']['previous_kurikoshisonekikin']) &&
                strlen(number_format(intval($datas['tax']['previous_kurikoshisonekikin']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['previous_kurikoshisonekikin']), $x_col1, 141.9 + $margin_line_top, 10, 5, 'R', 9);
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['previous_kurikoshisonekikin']), $x_col2, 141.9 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['next_kurikoshisonekikin']) &&
                strlen(number_format(intval($datas['tax']['next_kurikoshisonekikin']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['next_kurikoshisonekikin']), $x_col3, 141.9 + $margin_line_top, 10, 5, 'R', 9);
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['next_kurikoshisonekikin']), $x_col4, 141.9 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 27
        if (!empty($datas['income']['IncomeTaxesPayable']['beggining_sum']) &&
                strlen(number_format(intval($datas['income']['IncomeTaxesPayable']['beggining_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['IncomeTaxesPayable']['beggining_sum']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['income']['decrease_sum']) &&
                strlen(number_format(intval($datas['income']['decrease_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['decrease_sum']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['income']['sum_cost']) &&
                strlen(number_format(intval($datas['income']['sum_cost']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['sum_cost']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['income']['end_sum']) &&
                strlen(number_format(intval($datas['income']['end_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['end_sum']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 28
        if (!empty($datas['tax']['beggining_houjinzei_sum']) &&
                strlen(number_format(intval($datas['tax']['beggining_houjinzei_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['beggining_houjinzei_sum']), $x_col1, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['decrease_houjin']) &&
                strlen(number_format(intval($datas['tax']['decrease_houjin']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['decrease_houjin']), $x_col2, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['this_company_sum']) &&
                strlen(number_format(intval($datas['tax']['this_company_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['this_company_sum']), $x_col3, 152.8 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['income']['kakutei_houjinzei']) &&
                strlen(number_format(intval($datas['income']['kakutei_houjinzei']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['kakutei_houjinzei']), $x_col3, 158.2 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['next_houjinzei']) &&
                strlen(number_format(intval($datas['tax']['next_houjinzei']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['next_houjinzei']), $x_col4, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 29
        $margin_line_top += 11;
        if (!empty($datas['tax']['beggining_prefecture_sum']) &&
                strlen(number_format(intval($datas['tax']['beggining_prefecture_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['beggining_prefecture_sum']), $x_col1, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['decrease_prefecture']) &&
                strlen(number_format(intval($datas['tax']['decrease_prefecture']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['decrease_prefecture']), $x_col2, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['this_prefecture_sum']) &&
                strlen(number_format(intval($datas['tax']['this_prefecture_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['this_prefecture_sum']), $x_col3, 152.8 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['income']['kakutei_prefecture']) &&
                strlen(number_format(intval($datas['income']['kakutei_prefecture']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['kakutei_prefecture']), $x_col3, 158.2 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['next_prefecture']) &&
                strlen(number_format(intval($datas['tax']['next_prefecture']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['next_prefecture']), $x_col4, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 30
        $margin_line_top += 11;
        if (!empty($datas['tax']['beggining_city_sum']) &&
                strlen(number_format(intval($datas['tax']['beggining_city_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['beggining_city_sum']), $x_col1, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['decrease_city']) &&
                strlen(number_format(intval($datas['tax']['decrease_city']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['decrease_city']), $x_col2, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['this_city_sum']) &&
                strlen(number_format(intval($datas['tax']['this_city_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['this_city_sum']), $x_col3, 152.8 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['income']['kakutei_city']) &&
                strlen(number_format(intval($datas['income']['kakutei_city']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['income']['kakutei_city']), $x_col3, 158.2 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['tax']['next_city']) &&
                strlen(number_format(intval($datas['tax']['next_city']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['tax']['next_city']), $x_col4, 155.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 31
        $margin_line_top += 16.5;
        if (!empty($datas['all_beggining_sum']) &&
                strlen(number_format(intval($datas['all_beggining_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['all_beggining_sum']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['all_decrease_sum']) &&
                strlen(number_format(intval($datas['all_decrease_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['all_decrease_sum']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['all_increase_sum']) &&
                strlen(number_format(intval($datas['all_increase_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['all_increase_sum']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['all_end_sum']) &&
                strlen(number_format(intval($datas['all_end_sum']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['all_end_sum']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 32
        $margin_line_top += 26.3;
        if (!empty($datas['main']['beggining_capital']) &&
                strlen(number_format(intval($datas['main']['beggining_capital']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['beggining_capital']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['shihonkin_decrease']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['shihonkin_decrease']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['shihonkin_decrease']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['shihonkin_increase']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['shihonkin_increase']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['shihonkin_increase']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['end_capital']) &&
                strlen(number_format(intval($datas['end_capital']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['end_capital']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 33
        $margin_line_top += 5.5;
        if (!empty($datas['main']['beggining_capital_reserve']) &&
                strlen(number_format(intval($datas['main']['beggining_capital_reserve']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['beggining_capital_reserve']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['shihonjyunbikin_decrease']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['shihonjyunbikin_decrease']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['shihonjyunbikin_decrease']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['shihonjyunbikin_increase']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['shihonjyunbikin_increase']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['shihonjyunbikin_increase']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['end_capital_reserve']) &&
                strlen(number_format(intval($datas['end_capital_reserve']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['end_capital_reserve']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }
        $pdf->SetAutoPageBreak(FALSE);
        //Line 34
        $margin_line_top += 5.5;
        $pdf->SetXY(42, 147.2 + $margin_line_top);
        $pdf->MultiCell(28, 5, $datas['main']['Schedules51']['other_capital_name'], 0, 'C');

        if (!empty($datas['main']['Schedules51']['beggining_other_capital']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['beggining_other_capital']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['beggining_other_capital']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['other_capital_decrease']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['other_capital_decrease']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['other_capital_decrease']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['other_capital_increase']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['other_capital_increase']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['other_capital_increase']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['end_other_capital']) &&
                strlen(number_format(intval($datas['main']['end_other_capital']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['end_other_capital']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 35
        $margin_line_top += 5.5;
        $pdf->SetXY(42, 147.2 + $margin_line_top);
        $pdf->MultiCell(28, 5, $datas['main']['Schedules51']['other_capital_name2'], 0, 'C');

        if (!empty($datas['main']['Schedules51']['beggining_other_capital2']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['beggining_other_capital2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['beggining_other_capital2']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['other_capital_decrease2']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['other_capital_decrease2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['other_capital_decrease2']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['Schedules51']['other_capital_increase2']) &&
                strlen(number_format(intval($datas['main']['Schedules51']['other_capital_increase2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['Schedules51']['other_capital_increase2']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['main']['end_other_capital2']) &&
                strlen(number_format(intval($datas['main']['end_other_capital2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['main']['end_other_capital2']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        //Line 36
        $margin_line_top += 5.5;
        if (!empty($datas['cal']['beggining2']) &&
                strlen(number_format(intval($datas['cal']['beggining2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['cal']['beggining2']), $x_col1, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['cal']['decrease2']) &&
                strlen(number_format(intval($datas['cal']['decrease2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['cal']['decrease2']), $x_col2, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['cal']['increase2']) &&
                strlen(number_format(intval($datas['cal']['increase2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['cal']['increase2']), $x_col3, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }

        if (!empty($datas['cal']['end2']) &&
                strlen(number_format(intval($datas['cal']['end2']))) < 14) {
            $this->_putBaseNumber($pdf, $font, intval($datas['cal']['end2']), $x_col4, 147.4 + $margin_line_top, 10, 5, 'R', 9);
        }
    }

    /**
     * Put loop data in export Schedules0501s
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $datas
     */
    function _putDataLoopSchedules0501s(&$pdf, $font, $datas) {
        $Term = ClassRegistry::init('Term');

        $term_id = CakeSession::read('Auth.User.term_id');
        $pdf->SetFont($font, null, 12, true);
        $term = $Term->find('first', array(
            'conditions' => array('Term.id' => $term_id,
        )));

        $y1 = date('Y', strtotime($term['Term']['account_beggining'])) - 1988;
        $m1 = date('n', strtotime($term['Term']['account_beggining']));
        $d1 = date('j', strtotime($term['Term']['account_beggining']));

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(119.5, 18);
        $pdf->MultiCell(10, 5, $y1, 0, 'C');
        $pdf->SetXY(126.5, 18);
        $pdf->MultiCell(10, 5, $m1, 0, 'C');
        $pdf->SetXY(133, 18);
        $pdf->MultiCell(10, 5, $d1, 0, 'C');

        $y2 = date('Y', strtotime($term['Term']['account_end'])) - 1988;
        $m2 = date('n', strtotime($term['Term']['account_end']));
        $d2 = date('j', strtotime($term['Term']['account_end']));

        $pdf->SetXY(119.5, 22);
        $pdf->MultiCell(10, 5, $y2, 0, 'C');
        $pdf->SetXY(126.5, 22);
        $pdf->MultiCell(10, 5, $m2, 0, 'C');
        $pdf->SetXY(133, 22);
        $pdf->MultiCell(10, 5, $d2, 0, 'C');

        $user = CakeSession::read('Auth.User');
        $name = $user['name'];
        $x = array('x1' => 151.1, 'x2' => 154.2);
        $y = array('y1' => 20.4, 'y2' => 19, 'y3' => null);
        $align = array('align1' => 'C', 'align2' => 'L');
        $pdf->SetTextColor(30, 30, 30);
        $this->_putBaseStringWithLimit($pdf, $font, 8, $name, 2, 30, 3, 51, $x, $y, $align);
    }

    /**
     * Put number to column
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param string $data
     * @param int $x
     * @param int $y
     * @param int $widthCell
     * @param int $heightCell
     * @param int $align
     * @param int $size
     */
    function _putBaseNumber(&$pdf, $font, $data, $x, $y, $widthCell, $heightCell, $align, $size, $margin = 1.6) {
        $data = number_format($data);

        $step = 0;
        for ($i = strlen($data) - 1; $i >= 0; $i--) {
            $element = mb_substr($data, $i, 1, 'utf-8');
            if ($element == '-') {
                $element = '△';
                $size = $size - 2;
                $y = $y + 0.5;
            }
            $pdf->SetFont($font, null, $size, true);
            $pdf->SetXY($x - $step, $y);
            $pdf->MultiCell($widthCell, 5, $element, 0, $align);
            $step += $margin;
        }
    }

    /**
     *
     * @param \FPDI         $pdf
     * @param \TCPDF_FONTS  $font
     * @return \FPDI
     */
    public function export_business_reports($pdf, $font)
    {
        App::uses('Utils', 'Lib');
        /*@var $AccountInfo \AccountInfo */
        $AccountInfo = ClassRegistry::init('AccountInfo');
        $datas = $AccountInfo->findForPDF();

        // 合計
        $datas['AccountInfo']['total_sales']       = $AccountInfo->sumMonthlyValues($datas, 'sales');
        $datas['AccountInfo']['total_purchase']    = $AccountInfo->sumMonthlyValues($datas, 'purchase');
        $datas['AccountInfo']['total_outsourcing'] = $AccountInfo->sumMonthlyValues($datas, 'outsourcing');
        $datas['AccountInfo']['total_salaries']    = $AccountInfo->sumMonthlyValues($datas, 'salaries');
        $datas['AccountInfo']['total_deposits']    = $AccountInfo->sumMonthlyValues($datas, 'deposits');
        $datas['AccountInfo']['total_employee']    = $AccountInfo->sumMonthlyValues($datas, 'employee');

        $pTermId = $AccountInfo->getPreviousTermId();
        $pData = $AccountInfo->findAccountInfo($datas['AccountInfo']['user_id'], $pTermId);

        // 前期の実績
        if(!$datas['AccountInfo']['prev_sales'] ){
          $datas['AccountInfo']['prev_sales']       = $AccountInfo->sumMonthlyValues($pData, 'sales');
        }
        if(!$datas['AccountInfo']['prev_purchase'] ){
          $datas['AccountInfo']['prev_purchase']    = $AccountInfo->sumMonthlyValues($pData, 'purchase');
        }
        if(!$datas['AccountInfo']['prev_outsourcing'] ){
          $datas['AccountInfo']['prev_outsourcing'] = $AccountInfo->sumMonthlyValues($pData, 'outsourcing');
        }
        if(!$datas['AccountInfo']['prev_salaries'] ){
          $datas['AccountInfo']['prev_salaries']    = $AccountInfo->sumMonthlyValues($pData, 'salaries');
        }
        if(!$datas['AccountInfo']['prev_deposits'] ){
          $datas['AccountInfo']['prev_deposits']    = $AccountInfo->sumMonthlyValues($pData, 'deposits');
        }
        if(!$datas['AccountInfo']['prev_employee'] ){
          $datas['AccountInfo']['prev_employee']    = $AccountInfo->sumMonthlyValues($pData, 'employee');
        }
        // 下３桁を切り捨てる（千円単位なので）
        // foreach ($datas['AccountInfo'] as $field => $value) {
        //     if (preg_match('/^(prev|total|monthly)\_(sales|purchase|outsourcing|salaries|deposits|employee)\d*$/', $field)) {
        //         $datas['AccountInfo'][$field] = is_null($value) ? null : intval(floor($value / 1000));
        //     }
        // }
//        Configure::write('debug', 2);
//        debug($datas); die();

        $tplName = 'jigyougaikyou.pdf';
        if (!file_exists($tplFile = WWW_ROOT. 'pdf'. DS. $tplName)) {
            throw new RuntimeException('Template not found');
        }

        // Set template file
        $pdf->setSourceFile($tplFile);
        $tplPage1 = $pdf->importPage(1);
        $tplPage2 = $pdf->importPage(2);

        // Set to use page 1
        $pdf->AddPage();
        $pdf->useTemplate($tplPage1, null, null, null, null, true);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetTextColor(60, 60, 60);

        $boxW = 4.2;
        $boxH = 4.2;
        $boxS = 0.771;

        // 整理番号
        $pdf->SetFont($font, null, 7, true);
        $this->printNumberToBoxes($pdf, $datas['User']['User']['seiri_num'], $boxW, $boxH, 149, 17.48, $boxS, 8, true, true);

        // 法人名
        //$pdf->SetFont($font, null, 7, true);
        $this->printTextToBlock($pdf, $datas['User']['NameList']['name'], 67, 7, 20, 27.5, 26, 2);

        // 自 平成 & 至 平成
        //$pdf->SetFont($font, null, 7, true);
        $begin = $this->convertHeiseiDate($datas['User']['Term']['account_beggining']);
        $end = $this->convertHeiseiDate($datas['User']['Term']['account_end']);
        $this->printDateToBoxes($pdf, sprintf('%04d-%02d-%02d', $begin['year'], $begin['month'], $begin['day']), $boxW, $boxH, 117, 23.4, $boxS, 6.5);
        $this->printDateToBoxes($pdf, sprintf('%04d-%02d-%02d', $end['year'], $end['month'], $end['day']), $boxW, $boxH, 117, 29.6, $boxS, 6.5);

        // 納税地
        //$pdf->SetFont($font, null, 7, true);
        $addresss = sprintf('%s%s%s', $datas['User']['NameList']['prefecture'], $datas['User']['NameList']['city'], $datas['User']['NameList']['address']);
        $this->printTextToBlock($pdf, $datas['User']['NameList']['post_number'], 71, 3.5, 23, 36, null);
        $this->printTextToBlock($pdf, $addresss, 74, 6.5, 20, 40, 29, 2);

        // 電話番号
        $pdf->SetFont($font, null, 8, true);
        $phoneNum  = explode('-', $datas['User']['NameList']['phone_number'], 3);
        $pdf->MultiCell(8, 4, $phoneNum[0], 0, 'C', false, 0, 112, 36.2);
        $pdf->MultiCell(8, 4, $phoneNum[1], 0, 'C', false, 0, 123, 36.2);
        $pdf->MultiCell(8, 4, $phoneNum[2], 0, 'C', false, 0, 136, 36.2);

        // ホームページアドレス
        $pdf->SetFont($font, null, 5, true);
        $this->printTextToBlock($pdf, $datas['User']['User']['HP_address'], 38, 5, 108.5, 41.3, 38, 2);

        // 応答者氏名
        $pdf->SetFont($font, null, 7, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['responder'], 29, 10, 161, 36, 10, 2);

        // 1 事業内容
        $pdf->SetFont($font, null, 8, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['business_contents'], 34, 35, 19, 49.5, 11, 9, 'J', 'T');
        // 2 支店・海外取引状況
        // 2-3 取引種類
        $pdf->SetFont($font, null, 6, true);
        $this->printCheckBox($pdf, $datas['AccountInfo']['overseas_deal'], $boxW, $boxH, array(
            3 => array(112.1, 48.1),
            2 => array(131.8, 48.1),
            1 => array(151.45, 48.1),
            4 => array(131.8, 48.1),
        ));
        $this->printCheckBox($pdf, $datas['AccountInfo']['overseas_deal'], $boxW, $boxH, array(
            4 => array(112.1, 48.1),
        ));
        $this->printTextToBlock($pdf, $datas['AccountInfo']['import_cuntry'], 23.5, 4, 115.6, 54.6, 10, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['export_cuntry'], 23.5, 4, 115.6, 60.5, 10, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['import_product'], 23.5, 4, 138.6, 54.6, 10, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['export_product'], 23.5, 4, 138.6, 60.5, 10, 1, 'C');
        $pdf->SetFont($font, null, 7, true);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['import_sum'], $boxW, $boxH, 163.75, 54.29, $boxS, 5);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['export_sum'], $boxW, $boxH, 163.75, 60.45, $boxS, 5);
        // 2-4 貿易外取引
        $tradeEnabled = ($datas['AccountInfo']['trade_tesuryo'] == 1) ||
                        ($datas['AccountInfo']['trade_loyality'] == 1) ||
                        ($datas['AccountInfo']['trade_ekimu'] == 1) ||
                        ($datas['AccountInfo']['trade_shoken'] == 1) ||
                        ($datas['AccountInfo']['trade_other'] == 1) ||
                        ($datas['AccountInfo']['trade_land'] == 1) ||
                        ($datas['AccountInfo']['not_trade_other'] == 1);
        $this->printCheckBox($pdf, $tradeEnabled ? 1 : 0, $boxW, $boxH, array(1 => array(112.15, 66.6), 0 => array(131.85, 66.6)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_tesuryo'], $boxW, $boxH, array(1 => array(112.15, 72.76)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_loyality'], $boxW, $boxH, array(1 => array(131.85, 72.76)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_ekimu'], $boxW, $boxH, array(1 => array(151.48, 72.76)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_shoken'], $boxW, $boxH, array(1 => array(171.21, 72.76)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_other'], $boxW, $boxH, array(1 => array(112.15, 78.92)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_land'], $boxW, $boxH, array(1 => array(131.85, 78.92)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['trade_loan'], $boxW, $boxH, array(1 => array(151.48, 78.92)));
        $this->printTextToBlock($pdf, $datas['AccountInfo']['not_trade_other'], 14, 4, 172.5, 79, 6, 1, 'C');

        // 3 期末従事員等の状況
        $pdf->SetFont($font, null, 7, true);
        foreach (array('employee_class2', 'employee_class3', 'employee_class4', 'employee_class5') as $i => $field) {
            $this->printTextToBlock($pdf, $datas['AccountInfo'][$field], 15, 5, 23.5, 90.5 + $i * 6.15);
        }
        foreach (array('employee_num1', 'employee_num2', 'employee_num3', 'employee_num4', 'employee_num5', 'employee_total', 'president_family', 'part_timers') as $i => $field) {
            $this->printNumberToBoxes($pdf, $datas['AccountInfo'][$field],  $boxW, $boxH, 40.83, 85 + $i * 6.15, $boxS, 4);
        }
        $this->printCheckBox($pdf, $datas['AccountInfo']['salary_form'], $boxW, $boxH, array(1 => array(33.27, 134.22), 2 => array(43.14, 134.22), 3 => array(53.1, 134.22)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['company_housing'], $boxW, $boxH, array(1 => array(33.27, 140.42), 2 => array(43.14, 140.42)));

        // 4 電子計算機の利用状況
        $this->printCheckBox($pdf, $datas['AccountInfo']['pc_use'], $boxW, $boxH, array(1 => array(77.62, 85.02), 2 => array(87.44, 85.02)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['e_deal'], $boxW, $boxH, array(1 => array(107.3, 85.02), 2 => array(117.1, 85.02)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['program'], $boxW, $boxH, array(
            1 => array(87.44, 91.18),
            2 => array(97.33, 91.18),
            3 => array(107.22, 91.18),
            4 => array(117.11, 91.18),
        ));
        foreach (array('kyuuyokanri', 'hanbaikanri', 'zaiko', 'seisan', 'koteishisan', 'zaimu', 'sonotakanri') as $i => $field) {
            $this->printCheckBox($pdf, $datas['AccountInfo'][$field], $boxW, $boxH, array(1 => array(87.44 + ($i%4) * 9.89, 97.34 + intval($i/4) * 6.16)));
        }
        $pdf->SetFont($font, null, 5, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['what_business_other'], 10, 3.5, 115.3, 104, 4, 1, 'C');
        $pdf->SetFont($font, null, 7, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['model_name'], 36, 3.5, 87, 111.2, 15, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['software_name'], 32, 4, 91.5, 116, 14, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['consignment_name'], 41.5, 4, 82, 123.2, 16, 1, 'C');
        $pdf->SetFont($font, null, 6, true);
        $this->printNumberToBlock($pdf, $datas['AccountInfo']['usage_fee'], 13, 2.8, 107, 108.4, 6, 'C');
        $this->printNumberToBlock($pdf, $datas['AccountInfo']['consignment_fee'], 8.5, 2.8, 112.5, 120.68, 6, 'C');
        $pdf->SetFont($font, null, 7, true);
        $this->printCheckBox($pdf, $datas['AccountInfo']['lan'], $boxW, $boxH, array(
            1 => array(82.55, 128.1),
            2 => array(97.28, 128.1),
            3 => array(112.05, 128.1),
        ));
        $this->printCheckBox($pdf, $datas['AccountInfo']['saving_media'], $boxW, $boxH, array(
            1 => array(82.55, 134.2),
            2 => array(97.28, 134.2),
            3 => array(112.05, 134.2),
            4 => array(82.55, 140.4),
            5 => array(97.25, 140.4),
        ));
        $this->printTextToBlock($pdf, $datas['AccountInfo']['other_saving_media'], 12, 4, 112, 140.6, 4, 1, 'C');

        // 5 経理の状況
        $pdf->SetFont($font, null, 6, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['watch_cash'], 23.5, 5.3, 145.5, 90.5, 10, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['watch_check'],  23.5, 5.3, 145.5, 96.67, 10, 1, 'C');
        $this->printCheckBox($pdf, $datas['AccountInfo']['family_check'], $boxW, $boxH, array(1 => array(171.3, 91.18), 0 => array(181.1, 91.18)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['family_check2'], $boxW, $boxH, array(1 => array(171.3, 97.34), 0 => array(181.1, 97.34)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['make_trial_balance'], $boxW, $boxH, array(
            1 => array(146.52, 103.49),
            3 => array(161.27, 103.49),
            2 => array(176.02, 103.49),
        ));
        $this->printCheckBox($pdf, $datas['AccountInfo']['kyuuyo'], $boxW, $boxH, array(1 => array(146.52, 109.7)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['hosyu'], $boxW, $boxH, array(1 => array(161.27, 109.7)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['interest'], $boxW, $boxH, array(1 => array(176.02, 109.7)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['haito'], $boxW, $boxH, array(1 => array(146.52, 115.81)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['hikyojyusha'], $boxW, $boxH, array(1 => array(161.27, 115.81)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['other_gensen'], $boxW, $boxH, array(1 => array(176.02, 115.81)));
        $pdf->SetFont($font, null, 6, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['trial_balance_term'], 6, 3, 164, 105.4, 2, 1, 'C');
        $pdf->SetFont($font, null, 8, true);
        // 5-4 消費税
        $this->printCheckBox($pdf, $datas['AccountInfo']['accounting_method'], $boxW, $boxH, array(
            2 => array(146.52, 122),
            1 => array(156.3, 122),
        ));
        $this->printCheckBox($pdf, $datas['AccountInfo']['accounting_method'], $boxW, $boxH, array(
            2 => array(171.1, 122),
            1 => array(180.9, 122),
        ));
        $this->printCheckBox($pdf, $datas['AccountInfo']['accounting_method'], $boxW, $boxH, array(
            2 => array(146.52, 128.16),
            1 => array(156.3, 128.16),
        ));
        $this->printCheckBox($pdf, $datas['AccountInfo']['accounting_method'], $boxW, $boxH, array(
            2 => array(171.1, 128.16),
            1 => array(180.9, 128.16),
        ));
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['consumption_tax_sales'], $boxW, $boxH, 149, 134.3, $boxS, 8);

        // 6 株主又は株式所有異動の有無
        $this->printCheckBox($pdf, $datas['AccountInfo']['shareholder_changing'], $boxW, $boxH, array(1 => array(171.3, 140.49), 2 => array(181.1, 140.49)));

        // 7 主要科目 (単位・千円) AccountInfo.outsourcing_cost
        $column1 = array(
            'sales_sum', 'sub_sales_sum', 'cost_of_sales', 'initial_inventory', 'materials_cost', 'labor_cost',
            'outsourcing_cost', 'final_inventory', 'depreciation', 'rent', 'gross_profit', 'board_members_compensation',
            'salaries_sum', 'entertainment_expenses', 'depreciation2', 'rent2', 'operating_profit', 'interest_expense', 'net_income'
        );
        $column2 = array(
            'assets_sum', 'cash_sum', 'note_receivables', 'account_receivables', 'inventory', 'loan', 'building', 'machine_cost',
            'vehicle', 'land', 'liabilities', 'note_payables', 'account_payables', 'individual_debts', 'other_debts', 'net_assets',
        );
        foreach ($column1 as $i => $field) {
            $this->printNumberToBoxes($pdf, $datas['AccountInfo'][$field], $boxW, $boxH, 57.9, 146.6 + $i * 6.16, $boxS, 9);
        }
        foreach ($column2 as $i => $field) {
            $this->printNumberToBoxes($pdf, $datas['AccountInfo'][$field], $boxW, $boxH, 144.04, 146.6 + $i * 6.16, $boxS, 9);
        }

        // 8 インターネットバンキング等の利用の有無
        $this->printCheckBox($pdf, $datas['AccountInfo']['internet_banking'], $boxW, $boxH, array(1 => array(129.25, 251.24), 2 => array(139.1, 251.24)));
        $this->printCheckBox($pdf, $datas['AccountInfo']['farm_banking'], $boxW, $boxH, array(1 => array(171.3, 251.24), 2 => array(181.1, 251.24)));

        // 9 役員又は役員報酬額の異動の有無
        $this->printCheckBox($pdf, $datas['AccountInfo']['board_members_changing'], $boxW, $boxH, array(1 => array(171.3, 257.4), 2 => array(181.1, 257.4)));

        // 10 代表者に対する報酬等の金額
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_competition'],      $boxW, $boxH, 65.25, 263.55, $boxS, 7);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_loan'],             $boxW, $boxH, 109.6, 263.55, $boxS, 7);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_suspense_payment'], $boxW, $boxH, 153.9, 263.55, $boxS, 7);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_rent'],             $boxW, $boxH, 21.05, 269.72, $boxS, 7);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_interest_expense'], $boxW, $boxH, 65.25, 269.72, $boxS, 7);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_debts'],            $boxW, $boxH, 109.6, 269.72, $boxS, 7);
        $this->printNumberToBoxes($pdf, $datas['AccountInfo']['president_suspense_receipt'], $boxW, $boxH, 153.9, 269.72, $boxS, 7);

        // Set to use page 2
        $pdf->AddPage();
        $pdf->useTemplate($tplPage2, null, null, null, null, true);
//        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetTextColor(60, 60, 60);

        // 11 事業形態
        // 11-1 兼業の状況
        $pdf->SetFont($font, null, 8, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['side_job_rate'], 10, 4, 84.25, 2.8, 5, 1, 'C');
        //$pdf->SetFont($font, null, 8, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['side_job'], 31, 4, 37, 2.8, 8, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['side_job_situation'], 80, 18, 21, 8.5, 27, 5, 'L', 'T');
        // 11-2 事業内容の特異性
        $this->printTextToBlock($pdf, $datas['AccountInfo']['business_difference'], 80, 43, 21, 30, 27, 12, 'L', 'T');
        // 11-3 売上区分
        $this->printTextToBlock($pdf, $datas['AccountInfo']['cash_rate'], 12, 4, 59, 76, 5, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['credit_rate'], 10.4, 4, 89, 76, 5, 1, 'C');

        // 12 主な設備等の状況
        $this->printTextToBlock($pdf, $datas['AccountInfo']['assets_situation'], 75, 75, 109.5, 4, 25, 21, 'L', 'T');

        // 13 決済日等の状況
        $this->printTextToBlock($pdf, $datas['AccountInfo']['sales_deadline'],              20, 4, 44.4, 82, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['purchases_deadline'],          20, 4, 44.4, 88, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['outsourcing_deadline'],        20, 4, 44.4, 94, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['salaries_deadline'],           20, 4, 44.4, 100, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['sales_settlement_date'],       20, 4, 81, 82, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['purchases_settlement_date'],   20, 4, 81, 88, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['outsourcing_settlement_date'], 20, 4, 81, 94, 4, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['salaries_settlement_date'],    20, 4, 81, 100, 4, 1, 'C');

        // 14 帳簿類の備付状況
        // for ($i = 1; $i <= 14; $i++) {
        //     $this->printTextToBlock($pdf, $datas['AccountInfo']['books' . $i], 42, 4, 14.5 + ($i-1)%2 * 46, 112.5 + ($i-1)%7 * 6.2, 9, 1, 'C');
        // }

        $this->printTextToBlock($pdf, $datas['AccountInfo']['books1'], 42, 4, 14.5, 112.7, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books2'], 42, 4, 59, 112.7, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books3'], 42, 4, 14.5, 119, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books4'], 42, 4, 59, 119, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books5'], 42, 4, 14.5, 125.2, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books6'], 42, 4, 59, 125.2, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books7'], 42, 4, 14.5, 131.3, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books8'], 42, 4, 59, 131.3, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books9'], 42, 4, 14.5, 137.4, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books10'], 42, 4, 59, 137.4, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books11'], 42, 4, 14.5, 143.5, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books12'], 42, 4, 59, 143.5, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books13'], 42, 4, 14.5, 149.7, 9, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['books14'], 42, 4, 59, 149.7, 9, 1, 'C');
        // 15 税理士の関与状況
        $pdf->SetFont($font, null, 6.3, true);
        $this->printTextToBlock($pdf, $datas['TaxAccountant']['name'], 57, 4, 126, 82, 14, 1, 'L');
        $addresss = sprintf('%s%s%s', $datas['TaxAccountant']['prefecture'], $datas['TaxAccountant']['city'], $datas['TaxAccountant']['address']);
        $this->printTextToBlock($pdf, $addresss, 58, 5.6, 126, 87.4, null, 2, 'L');
        $this->printTextToBlock($pdf, $datas['TaxAccountant']['phone_number'], 57, 4, 126, 94.4, 13, 1, 'L');
        $pdf->SetFont($font, null, 8, true);
        // 15-4 関与状況
        $this->printCheckBox($pdf, $datas['AccountInfo']['shinkoku'], $boxW, $boxH, array(1 => array(126.2, 100.3))); // 申告書の作成
        $this->printCheckBox($pdf, $datas['AccountInfo']['tachiai'],  $boxW, $boxH, array(1 => array(145.9, 100.3))); // 調査立会
        $this->printCheckBox($pdf, $datas['AccountInfo']['soudan'],   $boxW, $boxH, array(1 => array(165.6, 100.3))); // 税務相談
        $this->printCheckBox($pdf, $datas['AccountInfo']['kessan'],   $boxW, $boxH, array(1 => array(126.2, 106.5))); // 決算書の作成
        $this->printCheckBox($pdf, $datas['AccountInfo']['denpyo'],   $boxW, $boxH, array(1 => array(145.9, 106.5))); // 伝票の整理
        $this->printCheckBox($pdf, $datas['AccountInfo']['hojyobo'],  $boxW, $boxH, array(1 => array(165.6, 106.5))); // 補助簿の記帳
        $this->printCheckBox($pdf, $datas['AccountInfo']['motocho'],  $boxW, $boxH, array(1 => array(126.2, 112.7))); // 総勘定元帳の記帳
        $this->printCheckBox($pdf, $datas['AccountInfo']['gensen'],   $boxW, $boxH, array(1 => array(155.8, 112.7))); // 源泉徴収関係事務

        // 16 加入組合等の状況
        $this->printTextToBlock($pdf, $datas['AccountInfo']['joining_union'], 76.5, 5, 107.5, 118.5, 28, 1, 'L');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['union_position'], 61.5, 5, 122.5, 124.66, 24, 1, 'L');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['joining_union2'], 76.5, 5, 107.5, 130.88, 28, 1, 'L');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['union_position2'], 61.5, 5, 122.5, 137.12, 24, 1, 'L');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['open'], 7.5, 5, 132, 143.1, 2, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['close'], 7.5, 5, 162, 143.1, 2, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['regular_holiday'], 13, 5, 142, 149.26, 5, 1, 'C');
        $this->printTextToBlock($pdf, $datas['AccountInfo']['regular_holiday2'], 13, 5, 164.5, 149.26, 5, 1, 'C');

        // 17 月別の売上高等の状況
        for ($i = 0; $i < 12; $i++) {
            $this->printTextToBlock($pdf, $datas['AccountInfo']['month'.(($i > 0?$i : '')+1)], 5.2, 6.16, 14.5, 167 + $i*6.16, 2, 1, 'R');
        }
        $pdf->SetFont($font, null, 7, true);
        for ($i = 0; $i < 12; $i++) {
            $sunfix = $i > 0 ? ($i+1) : '';

            $this->printNumberToBlock($pdf, $datas['AccountInfo']['monthly_sales' . $sunfix],       18, 5, 24, 168.5 + $i*6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo']['monthly_purchase' . $sunfix],    18, 5, 63.5, 168.5 + $i*6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo']['monthly_outsourcing' . $sunfix], 18, 5, 103, 168.5 + $i*6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo']['monthly_salaries' . $sunfix],    18.5, 5, 122.5, 168.5 + $i*6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo']['monthly_deposits' . $sunfix],    18.5, 5, 142, 168.5 + $i*6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo']['monthly_employee' . $sunfix],    8.5, 5, 175.5, 168.5 + $i*6.16, 5);
        }
        foreach (array('total_', 'prev_') as $i => $prefix) {
            $this->printNumberToBlock($pdf, $datas['AccountInfo'][$prefix . 'sales'],       18, 5, 24, 242.42 + $i * 6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo'][$prefix . 'purchase'],    18, 5, 63.5, 242.42 + $i * 6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo'][$prefix . 'outsourcing'], 18, 5, 103, 242.42 + $i * 6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo'][$prefix . 'salaries'],    18.5, 5, 122.5, 242.42 + $i * 6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo'][$prefix . 'deposits'],    18.5, 5, 142, 242.42 + $i * 6.16, 10);
            $this->printNumberToBlock($pdf, $datas['AccountInfo'][$prefix . 'employee'],    8.5, 5, 175.5, 242.42 + $i * 6.16, 5);
        }

        // 18 成績の概要 当期の営業
        $pdf->SetFont($font, null, 8, true);
        $this->printTextToBlock($pdf, $datas['AccountInfo']['operating_results'], 163, 20, 21.3, 255.3, 56, 5, 'L', 'T');

        return $pdf;
    }

    /**
     * Print a text to block with multi lines and align
     *
     * @param FPDI   $pdf
     * @param string $text      Text to print
     * @param float  $w         Width of block
     * @param float  $h         Height of block
     * @param float  $x         X coordinate of block
     * @param float  $y         Y coordinate of block
     * @param int    $lpl       Max string length per line. NULL for no limited
     * @param int    $line      Max number lines
     * @param string $align     L R J
     */
    private function printTextToBlock(&$pdf, $text, $w, $h, $x, $y, $lpl = null, $line = 1, $align = 'L', $valign = 'M')
    {
//        $text = str_pad('', 9999, '成績の概要当期の営業');
        if (!is_null($lpl)) {
            if (mb_strlen($text, 'utf-8') > ($lpl * $line)) {
                $text = mb_substr($text, 0, ($lpl * $line), 'utf-8');
            }
            $lines = array();
            for ($i = 0; $i < $line; $i++) {
                if (mb_strlen($text, 'utf-8') > $lpl) {
                    $lines[] = mb_substr($text, 0, $lpl, 'utf-8');
                    $text = mb_substr($text, $lpl, null, 'utf-8');
                } else {
                    $lines[] = $text;
                    break;
                }
            }
            $text = implode("\n", $lines);
        }

        $pdf->MultiCell($w, $h, $text, 0, $align, false, 0, $x, $y, true, 0, false, true, $h, $valign);
    }

    /**
     * Print a number to block with max length and align
     *
     * @param FPDI     $pdf
     * @param number   $num
     * @param float    $w
     * @param float    $h
     * @param float    $x
     * @param float    $y
     * @param int|null $maxLength
     * @param string   $align
     * @param string   $valign
     * @return void
     */
    private function printNumberToBlock(&$pdf, $num, $w, $h, $x, $y, $maxLength = null, $align = 'R', $valign = 'M')
    {
        if (is_null($num) || $num === '') {
            return;
        }
        if (!is_null($maxLength) && strlen('' . $num) > $maxLength) {
            $num = substr('' . $num, - $maxLength);
        }
//        $num = str_pad('', is_null($maxLength)? 9 : $maxLength, '9');
        $this->printTextToBlock($pdf, number_format($num), $w, $h, $x, $y, null, 1, $align, $valign);
    }

    /**
     * Print a date string to boxes (⬜⬜年⬜⬜月⬜⬜日)
     *
     * @param FPDI   $pdf
     * @param string $date      Date string as 'YYYY-MM-DD' format
     * @param float  $w         Width of a box
     * @param float  $h         Height of a box
     * @param float  $x         X coordinate of first box
     * @param float  $y         Y coordinate of first box
     * @param float  $spacing1  Short space of two boxes (⬜⬜)
     * @param float  $spacing2  Long space of two boxes (⬜ ⬜)
     */
    private function printDateToBoxes(&$pdf, $date, $w, $h, $x, $y, $spacing1, $spacing2)
    {
        $str = preg_replace('/^\d{2}(\d{2})\-(\d{2})\-(\d{2})$/', '$1$2$3', $date, 1, $count);
        if ($count <= 0) {
            throw new InvalidArgumentException('Date string must be formated as YYYY-MM-DD');
        }
        for($i = 0; $i < 6; $i++) {
            $pdf->MultiCell($w, $h, $str[$i], 0, 'C', false, 0, $x + $i * $w + ($i%2) * $spacing1 + ($i-$i%2)/2 * $spacing2, $y, true, 0, false, true, $h, 'M');
        }
    }

    /**
     * Print a string to boxes (⬜⬜⬜⬜⬜⬜⬜⬜⬜)
     * @param FPDI   $pdf
     * @param string $number    Number string
     * @param float  $w         Width of a box
     * @param float  $h         Height of a box
     * @param float  $x         X coordinate of first box
     * @param float  $y         Y coordinate of first box
     * @param float  $spacing   Space of two boxes (⬜⬜)
     * @param int    $maxLength Max length of string
     * @param bool   $alignRight Align right to left
     * @param bool   $prefill   FALSE for not pre fill; otherwise pre fill by zero
     * @param string $fillChar  Default '0'
     */
    private function printNumberToBoxes(&$pdf, $number, $w, $h, $x, $y, $spacing, $maxLength, $alignRight = true, $prefill = false, $fillChar = '0')
    {
        $str = trim('' . $number);
        $length = mb_strlen($str);
        if ($alignRight && !$prefill) {
            $prefill = true;
            $fillChar = ' ';
        }
        if ($length > $maxLength) {
            $str = mb_substr($str, - $maxLength);
        } elseif ($length < $maxLength && $prefill) {
            $str = str_pad($str, $maxLength, $fillChar, $alignRight ? STR_PAD_LEFT : STR_PAD_RIGHT);
        }
//        $str = str_pad('', $maxLength, '9');

        for($i = 0; $i < $maxLength; $i++) {
            $pdf->MultiCell($w, $h, mb_substr($str, $i, 1), 0, 'C', false, 0, $x + $i * ($w + $spacing), $y, true, 0, false, true, $h, 'M');
        }
    }

    /**
     * Print checked box with value
     *
     * @param FPDI      $pdf
     * @param int|string|array $value
     * @param float     $w       Width of a box
     * @param float     $h       Height of a box
     * @param array     $options List options, [$value => [$x, $y]]
     * @param bool      $fill
     */
    private function printCheckBox(&$pdf, $value, $w, $h, $options = array(), $fill = false)
    {
//        foreach ($options as $val) {
//            list ($x, $y) = $val;
//            $pdf->Circle($x + $w/2, $y + $h/2, min($w/2, $h/2) - 0.6);
//        }
        if (is_array($value)) {
            foreach ($value as $val) {
                $this->printCheckBox($pdf, $val, $w, $h, $options, $fill);
            }
        } else {
            if (isset($options[$value]) && is_array($options[$value])) {
                list ($x, $y) = $options[$value];
                $pdf->Circle($x + $w/2, $y + $h/2, min($w/2, $h/2) - 0.6);
            }
        }
    }

    /**
     * チェックマーク（レ点）出力
     * @param FPDI      $pdf
     * @param int|string|array $value
     * @param array     $options List options, [$value => [$x, $y]]
     */
    private function printCheckMark(&$pdf, $value, $options = array()) {
        if (is_array($value)) {
            foreach ($value as $val) {
                $this->printCheckMark($pdf, $val, $options);
            }
        } else {
            if (isset($options[$value]) && is_array($options[$value])) {
                list ($x, $y) = $options[$value];
                $pdf->SetXY($x, $y);
                $pdf->Cell(0, 0, 'レ', 0, 'C');
            }
        }
    }

    /**
     * Put string with limit character
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param int $font_size
     * @param string $data
     * @param int $line
     * @param int $width
     * @param int $margin_line
     * @param int $widthCell
     * @param array $x
     * @param array $y
     * @param array $align
     */
    function _putBaseStringWithLimit(&$pdf, $font, $font_size, $data, $line, $width, $margin_line, $widthCell, array $x, array $y, array $align) {
        $splitStr = array();
        $str = $data;
        for ($i = 0; $i < $line; $i++) {
            if ($width < mb_strwidth($str, 'utf-8')) {
                $splitStr[$i] = mb_strimwidth($str, 0, $width, '', 'utf-8');
                $str = mb_substr($str, mb_strlen($splitStr[$i], 'utf-8'), null, 'utf-8');
            } else {
                $splitStr[$i] = $str;
                break;
            }
        }

        $newStr = implode("", $splitStr);
        if (mb_strwidth($newStr, 'utf8') <= $width) {
            $height = $y['y1'];
        } elseif ((mb_strwidth($newStr, 'utf8') > $width) && (mb_strwidth($newStr, 'utf8') <= $width * 2) && !empty($y['y2'])) {
            $height = $y['y2'];
        } elseif ((mb_strwidth($newStr, 'utf8') > $width * 2) && !empty($y['y3'])) {
            $height = $y['y3'];
        }

        $pdf->SetFont($font, null, $font_size, true);
        $margin_top = 0;
        foreach ($splitStr as $element) {
            if (count($splitStr) == 1 && !empty($x['x1'])) {
                $pdf->SetXY($x['x1'], $height + $margin_top);
                $pdf->MultiCell($widthCell, 5, $element, 0, $align['align1']);
            } else {
                $pdf->SetXY($x['x2'], $height + $margin_top);
                $pdf->MultiCell($widthCell, 5, $element, 0, $align['align2']);
            }
            $margin_top += $margin_line;
        }
    }

    /**
     * Put each int number to cell
     * @param FPDI OBJ $pdf
     * @param array $x
     * @param array $y
     * @param array $data
     * @param array $distance
     * @param string $align
     * @param int $margin1
     */
    function _putIntNumber(&$pdf, $x, $y, $data, $distance, $align, $margin1) {
		$data = strval($data);
        $step = 0;
        for ($i = strlen($data) - 1; $i >= 0; $i--) {
            $element = mb_substr($data, $i, 1, 'utf-8');
            if ($element == '-') {
                $element = '△';
                $pdf->SetXY($x - $step + $margin1, $y);
            } else {
                $pdf->SetXY($x - $step, $y);
            }
            $pdf->MultiCell(10, 5, $element, 0, $align);
            $step += $distance;
        }
    }

    /**
     * 租税公課の納付状況等に関する明細書PDF生成
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_schedules0502s($pdf, $font) {

      $FixedAsset = ClassRegistry::init('FixedAsset');

      //事業年度で様式選択
      $term_info = $FixedAsset->getCurrentTerm();
      $target_day = '2016/01/01';
      $target_day29 = '2017/04/01';
      if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'schedules5_2_e290401.pdf');
      } else if(strtotime($target_day) > strtotime($term_info['Term']['account_beggining'])){
        $template = $this->setTemplateAddPage($pdf, $font, 'e270401_schedules5_2.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 's280101schedules5_2.pdf');
      }

        $TaxCalculation = ClassRegistry::init('TaxCalculation');
        $Schedules4 = ClassRegistry::init('Schedules4');
        $Schedules14 = ClassRegistry::init('Schedules14');
        $Schedules7 = ClassRegistry::init('Schedules7');
        $Schedules168 = ClassRegistry::init('Schedules168');

        //減価償却超過額・認容額を計算
        $Schedules168->findPdfExportData();
        $data16 = $FixedAsset->depreciationTotalCal();

        //寄付金損金不算入計算のために仮計取得
        $preSum = $Schedules4->calPreSum($data16['plus'], $data16['minus']);

        //寄付金の損金不算入額を取得
        $user = CakeSession::read('Auth.User');
        if($user['public_class'] == 1){
          $data14['not_cost'] = $Schedules14->find14Calres();
        } else {
          $data14 = $Schedules14->findFor14($preSum);
        }

        $data7 = $Schedules7->findForIndex7($preSum, $data14['not_cost']);

        $datas = $TaxCalculation->findFor52($preSum, $data14['not_cost'], $data7['this_deduction_sum']);

        $y1 = date('Y', strtotime($datas['user']['Term']['account_beggining'])) - 1988;
        $m1 = date('n', strtotime($datas['user']['Term']['account_beggining']));
        $d1 = date('j', strtotime($datas['user']['Term']['account_beggining']));

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(108.8, 17.5);
        $pdf->MultiCell(10, 5, $y1, 0, 'C');
        $pdf->SetXY(117, 17.5);
        $pdf->MultiCell(10, 5, $m1, 0, 'C');
        $pdf->SetXY(124.8, 17.5);
        $pdf->MultiCell(10, 5, $d1, 0, 'C');

        $y2 = date('Y', strtotime($datas['user']['Term']['account_end'])) - 1988;
        $m2 = date('n', strtotime($datas['user']['Term']['account_end']));
        $d2 = date('j', strtotime($datas['user']['Term']['account_end']));

        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(108.8, 23);
        $pdf->MultiCell(10, 5, $y2, 0, 'C');
        $pdf->SetXY(117, 23);
        $pdf->MultiCell(10, 5, $m2, 0, 'C');
        $pdf->SetXY(124.8, 23);
        $pdf->MultiCell(10, 5, $d2, 0, 'C');

        $name = h($datas['user']['User']['name']);
        $x = array('x1' => null, 'x2' => 147.8);
        $y = array('y1' => 21, 'y2' => 19.8, 'y3' => null);
        $align = array('align1' => null, 'align2' => 'L');
        $pdf->SetTextColor(30, 30, 30);
        $this->_putBaseStringWithLimit($pdf, $font, 7.5, $name, 2, 30, 2.8, 60, $x, $y, $align);

        $pdf->SetTextColor(60, 60, 60);
        $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 45);
        if (!empty($datas['term'])) {
            $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 51.25);
        }

        $x_col1 = 69;
        $x_col2 = 89;
        $x_col3 = 109;
        $x_col4 = 129;
        $x_col5 = 149.5;
        $x_col6 = 169.8;
        $pdf->SetTextColor(20, 20, 20);

        // Line 1
        $y1 = 47.6;
        $beggining_more_previous_company_tax = $datas['middle']['TaxCalculation']['beggining_more_previous_company_tax'];
        if (!empty($beggining_more_previous_company_tax) &&
                strlen(number_format($beggining_more_previous_company_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_more_previous_company_tax, $x_col1, $y1, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_company_tax = $datas['middle']['TaxCalculation']['more_previous_company_tax'];
        if (!empty($more_previous_company_tax) &&
                strlen(number_format($more_previous_company_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_company_tax, $x_col3, $y1, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_company_taxC = $datas['middle']['TaxCalculation']['more_previous_company_taxC'];
        if (!empty($more_previous_company_taxC) &&
                strlen(number_format($more_previous_company_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_company_taxC, $x_col5, $y1, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_houjinzei_cal = $datas['middle']['more_previous_houjinzei_cal'];
        if (!empty($beggining_more_previous_company_tax) &&
                strlen(number_format($more_previous_houjinzei_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_houjinzei_cal, $x_col6, $y1, 20, 5, 'R', 9, 1.4);
        }

        // Line 2
        $y2 = 53.8;
        $beggining_previous_company_tax = $datas['middle']['TaxCalculation']['beggining_previous_company_tax'];
        if (!empty($beggining_previous_company_tax) &&
                strlen(number_format($beggining_previous_company_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_previous_company_tax, $x_col1, $y2, 20, 5, 'R', 9, 1.4);
        }

        $previous_company_tax = $datas['middle']['TaxCalculation']['previous_company_tax'];
        if (!empty($previous_company_tax) &&
                strlen(number_format($previous_company_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_company_tax, $x_col3, $y2, 20, 5, 'R', 9, 1.4);
        }

        $previous_company_taxC = $datas['middle']['TaxCalculation']['previous_company_taxC'];
        if (!empty($previous_company_taxC) &&
                strlen(number_format($previous_company_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_company_taxC, $x_col5, $y2, 20, 5, 'R', 9, 1.4);
        }

        $previous_houjinzei_cal = $datas['middle']['previous_houjinzei_cal'];
        if (!empty($beggining_previous_company_tax) &&
                strlen(number_format($previous_houjinzei_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_houjinzei_cal, $x_col6, $y2, 20, 5, 'R', 9, 1.4);
        }

        // Line 3
        $y3 = 60;
        $this_company_sum = $datas['middle']['this_company_sum'];
        if (!empty($this_company_sum) &&
                strlen(number_format($this_company_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_company_sum, $x_col2, $y3, 20, 5, 'R', 9, 1.4);
        }

        $this_houjin_nouzeijyutoukin = $datas['middle']['this_houjin_nouzeijyutoukin'];
        if (!empty($this_houjin_nouzeijyutoukin) &&
                strlen(number_format($this_houjin_nouzeijyutoukin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_houjin_nouzeijyutoukin, $x_col3, $y3, 20, 5, 'R', 9, 1.4);
        }

        $this_cost_houjin = $datas['middle']['this_cost_houjin'];
        if (!empty($this_cost_houjin) &&
                strlen(number_format($this_cost_houjin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_cost_houjin, $x_col5, $y3, 20, 5, 'R', 9, 1.4);
        }

        $this_houjinzei_cal = $datas['middle']['this_houjinzei_cal'];
        if (!empty($this_company_sum) &&
                strlen(number_format($this_houjinzei_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_houjinzei_cal, $x_col6, $y3, 20, 5, 'R', 9, 1.4);
        }

        // Line 4
        $kakutei_houjinzei = $datas['bottom']['kakutei_houjinzei2'];
        $y4 = 66;
        if (!empty($kakutei_houjinzei) &&
                strlen(number_format($kakutei_houjinzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kakutei_houjinzei, $x_col2, $y4, 20, 5, 'R', 9, 1.4);
            if($datas['kanpu']['down_kakutei_houjin']){
              $kakutei_houjinzei = $datas['kanpu']['down_kakutei_houjin'];
              $y4 = $y4 + 1.5;
              $this->_putBaseNumber($pdf, $font, $kakutei_houjinzei, $x_col6, $y4, 20, 5, 'R', 9, 1.4);
              // Line 4　外書
              if($datas['kanpu']['up_kakutei_houjin']){
                $kakutei_houjinzei = $datas['kanpu']['up_kakutei_houjin'];
                $y4 = $y4 -3.3 ;
                $this->_putBaseNumber($pdf, $font, $kakutei_houjinzei, $x_col6, $y4, 20, 5, 'R', 9, 1.4);
              }
            } else {
              if($kakutei_houjinzei<0){
                $y4 = $y4 -1.3;
              }
              $this->_putBaseNumber($pdf, $font, $kakutei_houjinzei, $x_col6, $y4, 20, 5, 'R', 9, 1.4);
            }
        }

        // Line 5
        $y5 = 72.2;
        $beggining_houjinzei_sum = $datas['middle']['beggining_houjinzei_sum'];
        if (!empty($beggining_houjinzei_sum) &&
                strlen(number_format($beggining_houjinzei_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_houjinzei_sum, $x_col1, $y5, 20, 5, 'R', 9, 1.4);
        }

        $this_houjinzei_sum = $datas['middle']['this_houjinzei_sum'];
        if (!empty($this_houjinzei_sum) &&
                strlen(number_format($this_houjinzei_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_houjinzei_sum, $x_col2, $y5, 20, 5, 'R', 9, 1.4);
        }

        $payable_houjinzei_sum = $datas['middle']['payable_houjinzei_sum'];
        if (!empty($payable_houjinzei_sum) &&
                strlen(number_format($payable_houjinzei_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $payable_houjinzei_sum, $x_col3, $y5, 20, 5, 'R', 9, 1.4);
        }

        $cost_houjinzei_sum = $datas['middle']['cost_houjinzei_sum'];
        if (!empty($cost_houjinzei_sum) &&
                strlen(number_format($cost_houjinzei_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $cost_houjinzei_sum, $x_col5, $y5, 20, 5, 'R', 9, 1.4);
        }

        $end_houjinzei_sum = $datas['middle']['end_houjinzei_sum'];
        if ((!empty($beggining_houjinzei_sum) || !empty($this_houjinzei_sum))) {
            $this->_putBaseNumber($pdf, $font, $end_houjinzei_sum, $x_col6, $y5, 20, 5, 'R', 9, 1.4);
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 78);
          if (!empty($datas['term'])) {
              $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 84.25);
          }
        } else {
          $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 76.75);
          if (!empty($datas['term'])) {
              $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 83);
          }
        }

        // Line 6
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y6 = 79.5;
        } else {
          $y6 = 79.2;
        }
        $beggining_more_previous_prefecture_tax = $datas['middle']['TaxCalculation']['beggining_more_previous_prefecture_tax'];
        if (!empty($beggining_more_previous_prefecture_tax) &&
                strlen(number_format($beggining_more_previous_prefecture_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_more_previous_prefecture_tax, $x_col1, $y6, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_prefecture_tax = $datas['middle']['TaxCalculation']['more_previous_prefecture_tax'];
        if (!empty($more_previous_prefecture_tax) &&
                strlen(number_format($more_previous_prefecture_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_prefecture_tax, $x_col3, $y6, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_prefecture_taxC = $datas['middle']['TaxCalculation']['more_previous_prefecture_taxC'];
        if (!empty($more_previous_prefecture_taxC) &&
                strlen(number_format($more_previous_prefecture_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_prefecture_taxC, $x_col5, $y6, 20, 5, 'R', 9, 1.4);
        }

        if (isset($datas['middle']['more_previous_prefecture_cal'])) {
            $more_previous_prefecture_tax_cal = $datas['middle']['more_previous_prefecture_cal'];
            if (!empty($beggining_more_previous_prefecture_tax) &&
                    strlen(number_format($more_previous_prefecture_tax_cal)) < 14) {
                $this->_putBaseNumber($pdf, $font, $more_previous_prefecture_tax_cal, $x_col6, $y6, 20, 5, 'R', 9, 1.4);
            }
        }

        // Line 7
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y7 = 86;
        } else {
          $y7 = 85.3;
        }
        $beggining_previous_prefecture_tax = $datas['middle']['TaxCalculation']['beggining_previous_prefecture_tax'];
        if (!empty($beggining_previous_prefecture_tax) &&
                strlen(number_format($beggining_previous_prefecture_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_previous_prefecture_tax, $x_col1, $y7, 20, 5, 'R', 9, 1.4);
        }

        $previous_prefecture_tax = $datas['middle']['TaxCalculation']['previous_prefecture_tax'];
        if (!empty($previous_prefecture_tax) &&
                strlen(number_format($previous_prefecture_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_prefecture_tax, $x_col3, $y7, 20, 5, 'R', 9, 1.4);
        }

        $previous_prefecture_taxC = $datas['middle']['TaxCalculation']['previous_prefecture_taxC'];
        if (!empty($previous_prefecture_taxC) &&
                strlen(number_format($previous_prefecture_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_prefecture_taxC, $x_col5, $y7, 20, 5, 'R', 9, 1.4);
        }

        if (isset($datas['middle']['previous_prefecture_cal'])) {
            $previous_prefecture_cal = $datas['middle']['previous_prefecture_cal'];
            if (!empty($beggining_previous_prefecture_tax) &&
                    strlen(number_format($previous_prefecture_cal)) < 14) {
                $this->_putBaseNumber($pdf, $font, $previous_prefecture_cal, $x_col6, $y7, 20, 5, 'R', 9, 1.4);
            }
        }

        // Line 8
        if(strtotime($target_day29) > strtotime($term_info['Term']['account_end'])){
          $y8 = 91.7;
          $this_rishiwari = $datas['middle']['TaxCalculation']['this_rishiwari'];
          if (!empty($this_rishiwari) &&
                  strlen(number_format($this_rishiwari)) < 14) {
              $this->_putBaseNumber($pdf, $font, $this_rishiwari, $x_col2, $y8, 20, 5, 'R', 9, 1.4);
          }

          $jyutokin_rishiwari= $datas['middle']['TaxCalculation']['jyutokin_rishiwari'];
          if (!empty($jyutokin_rishiwari) &&
                  strlen(number_format($jyutokin_rishiwari)) < 14) {
              $this->_putBaseNumber($pdf, $font, $jyutokin_rishiwari, $x_col3, $y8, 20, 5, 'R', 9, 1.4);
          }

          $sonkin_rishiwari = $datas['middle']['TaxCalculation']['sonkin_rishiwari'];
          if (!empty($sonkin_rishiwari) &&
                  strlen(number_format($sonkin_rishiwari)) < 14) {
              $this->_putBaseNumber($pdf, $font, $sonkin_rishiwari, $x_col5, $y8, 20, 5, 'R', 9, 1.4);
          }

          $this_rishiwari_cal = $datas['middle']['this_rishiwari_cal'];
          $this->_putBaseNumber($pdf, $font, $this_rishiwari_cal, $x_col6, $y8, 20, 5, 'R', 9, 1.4);

        }

        // Line 9
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y9 = 92.5;
        } else {
          $y9 = 97.7;
        }
        $this_prefecture_sum = $datas['middle']['this_prefecture_sum'];
        if (!empty($this_prefecture_sum) &&
                strlen(number_format($this_prefecture_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_prefecture_sum, $x_col2, $y9, 20, 5, 'R', 9, 1.4);
        }

        $this_doufu_nouzeijyutoukin = $datas['middle']['this_doufu_nouzeijyutoukin'];
        if (!empty($this_doufu_nouzeijyutoukin) &&
                strlen(number_format($this_doufu_nouzeijyutoukin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_doufu_nouzeijyutoukin, $x_col3, $y9, 20, 5, 'R', 9, 1.4);
        }

        $this_cost_doufu = $datas['middle']['this_cost_doufu'];
        if (!empty($this_cost_doufu) &&
                strlen(number_format($this_cost_doufu)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_cost_doufu, $x_col5, $y9, 20, 5, 'R', 9, 1.4);
        }

        $this_prefecture_cal = $datas['middle']['this_prefecture_cal'];
        if (!empty($this_prefecture_sum) &&
                strlen(number_format($this_prefecture_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_prefecture_cal, $x_col6, $y9, 20, 5, 'R', 9, 1.4);
        }

        // Line 10
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y10 = 99;
        } else {
          $y10 = 104.1;
        }
        $kakutei_prefecture = $datas['bottom']['kakutei_prefecture2'];
        if (!empty($kakutei_prefecture) &&
                strlen(number_format($kakutei_prefecture)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kakutei_prefecture, $x_col2, $y10, 20, 5, 'R', 9, 1.4);
            if($datas['kanpu']['down_kakutei_prefecture']){
              $kakutei_prefecture = $datas['kanpu']['down_kakutei_prefecture'];
              $y10 = $y10 + 1.1;
              $this->_putBaseNumber($pdf, $font, $kakutei_prefecture, $x_col6, $y10, 20, 5, 'R', 9, 1.4);
              // Line 4　外書
              if($datas['kanpu']['up_kakutei_prefecture']){
                $kakutei_prefecture = $datas['kanpu']['up_kakutei_prefecture'];
                $y10 = $y10 -3.2 ;
                $this->_putBaseNumber($pdf, $font, $kakutei_prefecture, $x_col6, $y10, 20, 5, 'R', 9, 1.4);
              }
            } else {
              if($kakutei_prefecture < 0){
                $y10 = $y10 -2;
              }
              $this->_putBaseNumber($pdf, $font, $kakutei_prefecture, $x_col6, $y10, 20, 5, 'R', 9, 1.4);
            }

        }

        // Line 11
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y11 = 105.2;
        } else {
          $y11 = 110.2;
        }
        if (isset($datas['middle']['beggining_prefecture_sum'])) {
            $beggining_prefecture_sum = $datas['middle']['beggining_prefecture_sum'];
            if (!empty($beggining_prefecture_sum) &&
                    strlen(number_format($beggining_prefecture_sum)) < 14) {
                $this->_putBaseNumber($pdf, $font, $beggining_prefecture_sum, $x_col1, $y11, 20, 5, 'R', 9, 1.4);
            }
        }

        $this_doufukenminzei_sum = $datas['middle']['this_doufukenminzei_sum'];
        if (!empty($this_doufukenminzei_sum) &&
                strlen(number_format($this_doufukenminzei_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_doufukenminzei_sum, $x_col2, $y11, 20, 5, 'R', 9, 1.4);
        }

        $payable_prefecture_sum = $datas['middle']['payable_prefecture_sum'];
        if (!empty($payable_prefecture_sum) &&
                strlen(number_format($payable_prefecture_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $payable_prefecture_sum, $x_col3, $y11, 20, 5, 'R', 9, 1.4);
        }

        $cost_prefecture_sum = $datas['middle']['cost_prefecture_sum'];
        if (!empty($cost_prefecture_sum) &&
                strlen(number_format($cost_prefecture_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $cost_prefecture_sum, $x_col5, $y11, 20, 5, 'R', 9, 1.4);
        }

        $end_doufukenminzei_sum = $datas['middle']['end_doufukenminzei_sum'];
        if (!empty($end_doufukenminzei_sum)) {
            $this->_putBaseNumber($pdf, $font, $end_doufukenminzei_sum, $x_col6, $y11, 20, 5, 'R', 9, 1.4);
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 110.5);
          if (!empty($datas['term'])) {
              $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 116.75);
          }
        } else {
          $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 114.75);
          if (!empty($datas['term'])) {
              $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 121);
          }
        }

        // Line 12
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y12 = 112.1;
        } else {
          $y12 = 117.1;
        }
        $beggining_more_previous_city_tax = $datas['middle']['TaxCalculation']['beggining_more_previous_city_tax'];
        if (!empty($beggining_more_previous_city_tax) &&
                strlen(number_format($beggining_more_previous_city_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_more_previous_city_tax, $x_col1, $y12, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_city_tax = $datas['middle']['TaxCalculation']['more_previous_city_tax'];
        if (!empty($more_previous_city_tax) &&
                strlen(number_format($more_previous_city_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_city_tax, $x_col3, $y12, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_city_taxC = $datas['middle']['TaxCalculation']['more_previous_city_taxC'];
        if (!empty($more_previous_city_taxC) &&
                strlen(number_format($more_previous_city_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_city_taxC, $x_col5, $y12, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_city_cal = $datas['middle']['more_previous_city_cal'];
        if (!empty($beggining_more_previous_city_tax) &&
                strlen(number_format($more_previous_city_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_city_cal, $x_col6, $y12, 20, 5, 'R', 9, 1.4);
        }

        // Line 13
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y13 = 119;
        } else {
          $y13 = 123.3;
        }
        $beggining_previous_city_tax = $datas['middle']['TaxCalculation']['beggining_previous_city_tax'];
        if (!empty($beggining_previous_city_tax) &&
                strlen(number_format($beggining_previous_city_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_previous_city_tax, $x_col1, $y13, 20, 5, 'R', 9, 1.4);
        }

        $previous_city_tax = $datas['middle']['TaxCalculation']['previous_city_tax'];
        if (!empty($previous_city_tax) &&
                strlen(number_format($previous_city_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_city_tax, $x_col3, $y13, 20, 5, 'R', 9, 1.4);
        }

        $previous_city_taxC = $datas['middle']['TaxCalculation']['previous_city_taxC'];
        if (!empty($previous_city_taxC) &&
                strlen(number_format($previous_city_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_city_taxC, $x_col5, $y13, 20, 5, 'R', 9, 1.4);
        }

        $previous_city_cal = $datas['middle']['previous_city_cal'];
        if (!empty($beggining_previous_city_tax) &&
                strlen(number_format($previous_city_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_city_cal, $x_col6, $y13, 20, 5, 'R', 9, 1.4);
        }

        // Line 14
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y14 = 125.5;
        } else {
          $y14 = 129.5;
        }
        $this_city_sum = $datas['middle']['this_city_sum'];
        if (!empty($this_city_sum) &&
                strlen(number_format($this_city_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_city_sum, $x_col2, $y14, 20, 5, 'R', 9, 1.4);
        }

        $this_sicho_nouzeijyutoukin = $datas['middle']['this_sicho_nouzeijyutoukin'];
        if (!empty($this_sicho_nouzeijyutoukin) &&
                strlen(number_format($this_sicho_nouzeijyutoukin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_sicho_nouzeijyutoukin, $x_col3, $y14, 20, 5, 'R', 9, 1.4);
        }

        $this_cost_sicho = $datas['middle']['this_cost_sicho'];
        if (!empty($this_cost_sicho) &&
                strlen(number_format($this_cost_sicho)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_cost_sicho, $x_col5, $y14, 20, 5, 'R', 9, 1.4);
        }

        $this_city_cal = $datas['middle']['this_city_cal'];
        if (!empty($this_city_sum) &&
                strlen(number_format($this_city_cal)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_city_cal, $x_col6, $y14, 20, 5, 'R', 9, 1.4);
        }

        // Line 15
        $kakutei_city = $datas['bottom']['kakutei_city2'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y15 = 131.8;
        } else {
          $y15 = 135.8;
        }
        if (!empty($kakutei_city) &&
                strlen(number_format($kakutei_city)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kakutei_city, $x_col2, $y15, 20, 5, 'R', 9, 1.4);
            if($datas['kanpu']['down_kakutei_city']){
              $kakutei_city = $datas['kanpu']['down_kakutei_city'];
              $y15 = $y15 + 1.1;
              $this->_putBaseNumber($pdf, $font, $kakutei_city, $x_col6, $y15, 20, 5, 'R', 9, 1.4);
              // Line 4　外書
              if($datas['kanpu']['up_kakutei_city']){
                $kakutei_city = $datas['kanpu']['up_kakutei_city'];
                $y15 = $y15 -3.2 ;
                $this->_putBaseNumber($pdf, $font, $kakutei_city, $x_col6, $y15, 20, 5, 'R', 9, 1.4);
              }
            } else {
              if($kakutei_city < 0){
                $y15 = $y15 -2;
              }
              $this->_putBaseNumber($pdf, $font, $kakutei_city, $x_col6, $y15, 20, 5, 'R', 9, 1.4);
            }
        }

        // Line 16
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y16 = 138;
        } else {
          $y16 = 142;
        }
        $beggining_city_sum = $datas['middle']['beggining_city_sum'];
        if (!empty($beggining_city_sum) &&
                strlen(number_format($beggining_city_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_city_sum, $x_col1, $y16, 20, 5, 'R', 9, 1.4);
        }

        $this_sicyouson_sum = $datas['middle']['this_sicyouson_sum'];
        if (!empty($this_sicyouson_sum) &&
                strlen(number_format($this_sicyouson_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_sicyouson_sum, $x_col2, $y16, 20, 5, 'R', 9, 1.4);
        }

        $payable_city_sum = $datas['middle']['payable_city_sum'];
        if (!empty($payable_city_sum) &&
                strlen(number_format($payable_city_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $payable_city_sum, $x_col3, $y16, 20, 5, 'R', 9, 1.4);
        }

        $cost_city_sum = $datas['middle']['cost_city_sum'];
        if (!empty($cost_city_sum) &&
                strlen(number_format($cost_city_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $cost_city_sum, $x_col5, $y16, 20, 5, 'R', 9, 1.4);
        }

        $end_sicyouson_sum = $datas['middle']['end_sicyouson_sum'];
        if (!empty($end_sicyouson_sum) ) {
            $this->_putBaseNumber($pdf, $font, $end_sicyouson_sum, $x_col6, $y16, 20, 5, 'R', 9, 1.4);
        }

        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 143.25);
          if (!empty($datas['term'])) {
              $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 149.5);
          }
        } else {
          $this->_putDateSchedules0502s($pdf, $font, $datas['middle']['TaxCalculation']['when_more_previous_beggining'], $datas['middle']['TaxCalculation']['when_more_previous_end'], 146.5);
          if (!empty($datas['term'])) {
              $this->_putDateSchedules0502s($pdf, $font, $datas['term']['Term']['account_beggining'], $datas['term']['Term']['account_end'], 152.75);
          }
        }

        // Line 17
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y17 = 145.5;
        } else {
          $y17 = 148.9;
        }
        $beggining_more_previous_business_tax = $datas['middle']['TaxCalculation']['beggining_more_previous_business_tax'];
        if (!empty($beggining_more_previous_business_tax) &&
                strlen(number_format($beggining_more_previous_business_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_more_previous_business_tax, $x_col1, $y17, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_business_tax = $datas['middle']['TaxCalculation']['more_previous_business_tax'];
        if (!empty($more_previous_business_tax) &&
                strlen(number_format($more_previous_business_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_business_tax, $x_col3, $y17, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_business_taxC = $datas['middle']['TaxCalculation']['more_previous_business_taxC'];
        if (!empty($more_previous_business_taxC) &&
                strlen(number_format($more_previous_business_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $more_previous_business_taxC, $x_col5, $y17, 20, 5, 'R', 9, 1.4);
        }

        $more_previous_business_cal = $datas['middle']['more_previous_business_cal'];
        if (!empty($beggining_more_previous_business_tax)) {
            $this->_putBaseNumber($pdf, $font, $more_previous_business_cal, $x_col6, $y17, 20, 5, 'R', 9, 1.4);
        }

        // Line 18
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y18 = 152;
        } else {
          $y18 = 155;
        }
        $beggining_previous_business_tax = $datas['middle']['TaxCalculation']['beggining_previous_business_tax'];
        if (!empty($beggining_previous_business_tax) &&
                strlen(number_format($beggining_previous_business_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_previous_business_tax, $x_col1, $y18, 20, 5, 'R', 9, 1.4);
        }

        $this_previous_business_tax = $datas['middle']['TaxCalculation']['this_previous_business_tax'];
        if (!empty($this_previous_business_tax) &&
                strlen(number_format($this_previous_business_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_previous_business_tax, $x_col2, $y18, 20, 5, 'R', 9, 1.4);
        }

        $previous_business_tax = $datas['middle']['TaxCalculation']['previous_business_tax'];
        if (!empty($previous_business_tax) &&
                strlen(number_format($previous_business_tax)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_business_tax, $x_col3, $y18, 20, 5, 'R', 9, 1.4);
        }

        $previous_business_taxC = $datas['middle']['TaxCalculation']['previous_business_taxC'];
        if (!empty($previous_business_taxC) &&
                strlen(number_format($previous_business_taxC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $previous_business_taxC, $x_col5, $y18, 20, 5, 'R', 9, 1.4);
        }

        $previous_business_cal = $datas['middle']['previous_business_cal'];
        if ((!empty($beggining_previous_business_tax) || !empty($this_previous_business_tax)) ) {
            $this->_putBaseNumber($pdf, $font, $previous_business_cal, $x_col6, $y18, 20, 5, 'R', 9, 1.4);
        }

        // Line 19
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y19 = 158.5;
        } else {
          $y19 = 161.2;
        }
        $this_business_sum = $datas['middle']['this_business_sum'];
        if (!empty($this_business_sum) &&
                strlen(number_format($this_business_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_business_sum, $x_col2, $y19, 20, 5, 'R', 9, 1.4);
        }

        $this_jigyo_nouzeijyutoukin = $datas['middle']['this_jigyo_nouzeijyutoukin'];
        if (!empty($this_jigyo_nouzeijyutoukin) &&
                strlen(number_format($this_jigyo_nouzeijyutoukin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_jigyo_nouzeijyutoukin, $x_col3, $y19, 20, 5, 'R', 9, 1.4);
        }

        $this_cost_jigyo = $datas['middle']['this_cost_jigyo'];
        if (!empty($this_cost_jigyo) &&
                strlen(number_format($this_cost_jigyo)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_cost_jigyo, $x_col5, $y19, 20, 5, 'R', 9, 1.4);
        }

        $this_business_cal = $datas['middle']['this_business_cal'];
        if (!empty($this_business_sum)) {
            $this->_putBaseNumber($pdf, $font, $this_business_cal, $x_col6, $y19, 20, 5, 'R', 9, 1.4);
        }

        // Line 20
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y20 = 165;
        } else {
          $y20 = 167.5;
        }
        $beggining_business_sum = $datas['middle']['beggining_business_sum'];
        if (!empty($beggining_business_sum) &&
                strlen(number_format($beggining_business_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_business_sum, $x_col1, $y20, 20, 5, 'R', 9, 1.4);
        }

        $total_this_business = $datas['middle']['total_this_business'];
        if (!empty($total_this_business) &&
                strlen(number_format($total_this_business)) < 14) {
            $this->_putBaseNumber($pdf, $font, $total_this_business, $x_col2, $y20, 20, 5, 'R', 9, 1.4);
        }

        $payable_business_sum = $datas['middle']['payable_business_sum'];
        if (!empty($payable_business_sum) &&
                strlen(number_format($payable_business_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $payable_business_sum, $x_col3, $y20, 20, 5, 'R', 9, 1.4);
        }

        $cost_business_sum = $datas['middle']['cost_business_sum'];
        if (!empty($cost_business_sum) &&
                strlen(number_format($cost_business_sum)) < 14) {
            $this->_putBaseNumber($pdf, $font, $cost_business_sum, $x_col5, $y20, 20, 5, 'R', 9, 1.4);
        }

        $end_jigyouzei_sum = $datas['middle']['end_jigyouzei_sum'];
        if ((!empty($beggining_business_sum) || !empty($total_this_business))) {
            $this->_putBaseNumber($pdf, $font, $end_jigyouzei_sum, $x_col6, $y20, 20, 5, 'R', 9, 1.4);
        }

        // Line 23
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y23 = 184.5;
        } else {
          $y23 = 186.5;
        }
        if ($datas['middle']['TaxCalculation']['beggining_shotokuzei'] > 0 || $datas['middle']['TaxCalculation']['this_shotokuzei'] > 0) {
            $pdf->MultiCell(30, 10, '源泉所得税等', 0, 'C',0,0,34.5,$y23);
        }
        $beggining_shotokuzei = $datas['middle']['TaxCalculation']['beggining_shotokuzei'];
        if (!empty($beggining_shotokuzei) &&
                strlen(number_format($beggining_shotokuzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_shotokuzei, $x_col1, $y23, 20, 5, 'R', 9, 1.4);
        }

        $this_shotokuzei = $datas['middle']['TaxCalculation']['this_shotokuzei'];
        if (!empty($this_shotokuzei) &&
                strlen(number_format($this_shotokuzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_shotokuzei, $x_col2, $y23, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzei = $datas['middle']['TaxCalculation']['shotokuzei'];
        if (!empty($shotokuzei) &&
                strlen(number_format($shotokuzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $shotokuzei, $x_col3, $y23, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzeiC = $datas['middle']['TaxCalculation']['shotokuzeiC'];
        if (!empty($shotokuzeiC) &&
                strlen(number_format($shotokuzeiC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $shotokuzeiC, $x_col5, $y23, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzei_cal = $datas['middle']['TaxCalculation']['shotokuzei_cal'];
        if ((!empty($beggining_shotokuzei) || !empty($this_shotokuzei)) &&
                strlen(number_format($shotokuzei_cal) < 14)) {
            $this->_putBaseNumber($pdf, $font, $shotokuzei_cal, $x_col6, $y23, 20, 5, 'R', 9, 1.4);
        }

        // Line 25
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y25 = 197.5;
        } else {
          $y25 = 199.25;
        }
        $beggining_kasanzei = $datas['middle']['TaxCalculation']['beggining_kasanzei'];
        if (!empty($beggining_kasanzei) &&
                strlen(number_format($beggining_kasanzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_kasanzei, $x_col1, $y25, 20, 5, 'R', 9, 1.4);
        }

        $this_kasanzei = $datas['middle']['TaxCalculation']['this_kasanzei'];
        if (!empty($this_kasanzei) &&
                strlen(number_format($this_kasanzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_kasanzei, $x_col2, $y25, 20, 5, 'R', 9, 1.4);
        }

        $kasanzei = $datas['middle']['TaxCalculation']['kasanzei'];
        if (!empty($kasanzei) &&
                strlen(number_format($kasanzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kasanzei, $x_col3, $y25, 20, 5, 'R', 9, 1.4);
        }

        $kasanzeiC = $datas['middle']['TaxCalculation']['kasanzeiC'];
        if (!empty($kasanzeiC) &&
                strlen(number_format($kasanzeiC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kasanzeiC, $x_col5, $y25, 20, 5, 'R', 9, 1.4);
        }

        $kasanzei_cal = $datas['middle']['kasanzei_cal'];
        if (!empty($beggining_kasanzei) || !empty($this_kasanzei)) {
            $this->_putBaseNumber($pdf, $font, $kasanzei_cal, $x_col6, $y25, 20, 5, 'R', 9, 1.4);
        }

        // Line 26
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y26 = 204;
        } else {
          $y26 = 205.3;
        }
        $beggining_entaizei = $datas['middle']['TaxCalculation']['beggining_entaizei'];
        if (!empty($beggining_entaizei) &&
                strlen(number_format($beggining_entaizei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_entaizei, $x_col1, $y26, 20, 5, 'R', 9, 1.4);
        }

        $this_entaizei = $datas['middle']['TaxCalculation']['this_entaizei'];
        if (!empty($this_entaizei) &&
                strlen(number_format($this_entaizei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_entaizei, $x_col2, $y26, 20, 5, 'R', 9, 1.4);
        }

        $entaizei = $datas['middle']['TaxCalculation']['entaizei'];
        if (!empty($entaizei) &&
                strlen(number_format($entaizei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $entaizei, $x_col3, $y26, 20, 5, 'R', 9, 1.4);
        }

        $entaizeiC = $datas['middle']['TaxCalculation']['entaizeiC'];
        if (!empty($entaizeiC) &&
                strlen(number_format($entaizeiC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $entaizeiC, $x_col5, $y26, 20, 5, 'R', 9, 1.4);
        }


        $entaizei_cal = $datas['middle']['entaizei_cal'];
        if ((!empty($beggining_entaizei) || !empty($this_entaizei)) &&
                strlen(number_format($entaizei_cal) < 14)) {
            $this->_putBaseNumber($pdf, $font, $entaizei_cal, $x_col6, $y26, 20, 5, 'R', 9, 1.4);
        }

        // Line 27
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y27 = 210.2;
        } else {
          $y27 = 211.6;
        }
        $beggining_entaikin = $datas['middle']['TaxCalculation']['beggining_entaikin'];
        if (!empty($beggining_entaikin) &&
                strlen(number_format($beggining_entaikin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_entaikin, $x_col1, $y27, 20, 5, 'R', 9, 1.4);
        }

        $this_entaikin = $datas['middle']['TaxCalculation']['this_entaikin'];
        if (!empty($this_entaikin) &&
                strlen(number_format($this_entaikin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_entaikin, $x_col2, $y27, 20, 5, 'R', 9, 1.4);
        }

        $entaikin = $datas['middle']['TaxCalculation']['entaikin'];
        if (!empty($entaikin) &&
                strlen(number_format($entaikin)) < 14) {
            $this->_putBaseNumber($pdf, $font, $entaikin, $x_col3, $y27, 20, 5, 'R', 9, 1.4);
        }

        $entaikinC = $datas['middle']['TaxCalculation']['entaikinC'];
        if (!empty($entaikinC) &&
                strlen(number_format($entaikinC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $entaikinC, $x_col5, $y27, 20, 5, 'R', 9, 1.4);
        }

        $entaikin_cal = $datas['middle']['entaikin_cal'];
        if ((!empty($this_entaikin) || !empty($beggining_entaikin)) &&
                strlen(number_format($entaikin_cal) < 14)) {
            $this->_putBaseNumber($pdf, $font, $entaikin_cal, $x_col6, $y27, 20, 5, 'R', 9, 1.4);
        }

        // Line 28
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y28 = 216.5;
        } else {
          $y28 = 217.8;
        }
        $beggining_kataizei = $datas['middle']['TaxCalculation']['beggining_kataizei'];
        if (!empty($beggining_kataizei) &&
                strlen(number_format($beggining_kataizei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_kataizei, $x_col1, $y28, 20, 5, 'R', 9, 1.4);
        }

        $this_kataizei = $datas['middle']['TaxCalculation']['this_kataizei'];
        if (!empty($this_kataizei) &&
                strlen(number_format($this_kataizei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_kataizei, $x_col2, $y28, 20, 5, 'R', 9, 1.4);
        }

        $kataizei = $datas['middle']['TaxCalculation']['kataizei'];
        if (!empty($kataizei) &&
                strlen(number_format($kataizei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kataizei, $x_col3, $y28, 20, 5, 'R', 9, 1.4);
        }

        $kataizeiC = $datas['middle']['TaxCalculation']['kataizeiC'];
        if (!empty($kataizeiC) &&
                strlen(number_format($kataizeiC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $kataizeiC, $x_col5, $y28, 20, 5, 'R', 9, 1.4);
        }

        $kataizei_cal = $datas['middle']['kataizei_cal'];
        if ((!empty($this_kataizei) || !empty($beggining_kataizei)) &&
                strlen(number_format($kataizei_cal) < 14)) {
            $this->_putBaseNumber($pdf, $font, $kataizei_cal, $x_col6, $y28, 20, 5, 'R', 9, 1.4);
        }

        //line 29
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y29 = 223;
        } else {
          $y29 = 224;
        }
        if ($datas['middle']['TaxCalculation']['this_shotokuzei_deduction'] > 0 ||$datas['middle']['TaxCalculation']['beggining_shotokuzei_deduction'] > 0 ) {
            $pdf->MultiCell(30, 10, '源泉所得税等', 0, 'C',0,0,34.5,$y29);
        }
        $beggining_shotokuzei = $datas['middle']['TaxCalculation']['beggining_shotokuzei_deduction'];
        if (!empty($beggining_shotokuzei) &&
                strlen(number_format($beggining_shotokuzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $beggining_shotokuzei, $x_col1, $y29, 20, 5, 'R', 9, 1.4);
        }

        $this_shotokuzei = $datas['middle']['TaxCalculation']['this_shotokuzei_deduction'];
        if (!empty($this_shotokuzei) &&
                strlen(number_format($this_shotokuzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $this_shotokuzei, $x_col2, $y29, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzei = $datas['middle']['TaxCalculation']['shotokuzei_deduction'];
        if (!empty($shotokuzei) &&
                strlen(number_format($shotokuzei)) < 14) {
            $this->_putBaseNumber($pdf, $font, $shotokuzei, $x_col3, $y29, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzeiS = $datas['middle']['TaxCalculation']['shotokuzei_deductionS'];
        if (!empty($shotokuzeiS) &&
                strlen(number_format($shotokuzeiS)) < 14) {
            $this->_putBaseNumber($pdf, $font, $shotokuzeiS, $x_col4, $y29, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzeiC = $datas['middle']['TaxCalculation']['shotokuzei_deductionC'];
        if (!empty($shotokuzeiC) &&
                strlen(number_format($shotokuzeiC)) < 14) {
            $this->_putBaseNumber($pdf, $font, $shotokuzeiC, $x_col5, $y29, 20, 5, 'R', 9, 1.4);
        }

        $shotokuzei_cal = $datas['middle']['shotokuzei_deduction_cal'];
        if ((!empty($this_shotokuzei) || !empty($beggining_shotokuzei)))  {
            $this->_putBaseNumber($pdf, $font, $shotokuzei_cal, $x_col6, $y29, 20, 5, 'R', 9, 1.4);
        }

        // Line 31
        $y31 = 242.9;
        $beggining_sum = $datas['bottom']['IncomeTaxesPayable']['beggining_sum'];
        if (!empty($beggining_sum)) {
            $this->_putBaseNumber($pdf, $font, $beggining_sum, $x_col2 + 2, $y31, 20, 5, 'R', 9);
        }

        // Line 32
        $y32 = 248.8;
        $cost_sum = $datas['bottom']['IncomeTaxesPayable']['cost_sum'];
        if (!empty($cost_sum)) {
            $this->_putBaseNumber($pdf, $font, $cost_sum, $x_col2 + 2, $y32, 20, 5, 'R', 9);
        }

        // Line 33
        $y33 = 255.15;
        $pdf->SetXY(25, 254.5);
        $pdf->MultiCell(45, 5, $datas['bottom']['IncomeTaxesPayable']['other_cost_name'], 0, 'C');

        $other_cost_sum = $datas['bottom']['IncomeTaxesPayable']['other_cost_sum'];
        if (!empty($other_cost_sum)) {
            $this->_putBaseNumber($pdf, $font, $other_cost_sum, $x_col2 + 2, $y33, 20, 5, 'R', 9);
        }

        // Line 34
        $y34 = 261.4;
        $sum_cost = $datas['bottom']['sum_cost'];
        if (!empty($sum_cost)) {
            $this->_putBaseNumber($pdf, $font, $sum_cost, $x_col2 + 2, $y34, 20, 5, 'R', 9);
        }

        // Line 35
        $y35 = 267.7;
        $houjinzeitou = $datas['bottom']['houjinzeitou'];
        if (!empty($houjinzeitou)) {
            $this->_putBaseNumber($pdf, $font, $houjinzeitou, $x_col2 + 2, $y35, 20, 5, 'R', 9);
        }

        // Line 36
        $y36 = 273.5;
        $jigyouzei = $datas['bottom']['jigyouzei'];
        if (!empty($jigyouzei)) {
            $pdf->SetAutoPageBreak(FALSE);
            $this->_putBaseNumber($pdf, $font, $jigyouzei, $x_col2 + 2, $y36, 20, 5, 'R', 9);
        }

        // Line 37
        $pay_other_cost = $datas['bottom']['IncomeTaxesPayable']['pay_other_cost'];
        if (!empty($pay_other_cost)) {
            $this->_putBaseNumber($pdf, $font, $pay_other_cost, $x_col6, $y31, 20, 5, 'R', 9);
        }

        // Line 38
        $not_cost_sum = $datas['bottom']['not_cost_sum'];
        if (!empty($not_cost_sum)) {
            $this->_putBaseNumber($pdf, $font, $not_cost_sum, $x_col6, $y32, 20, 5, 'R', 9);
        }

        // Line 39
        $pdf->SetXY(121, 254.5);
        $pdf->MultiCell(37, 5, $datas['bottom']['IncomeTaxesPayable']['pay_other_name'], 0, 'C');

        $pay_other_sum = $datas['bottom']['IncomeTaxesPayable']['pay_other_sum'];
        if (!empty($pay_other_sum)) {
            $this->_putBaseNumber($pdf, $font, $pay_other_sum, $x_col6, $y33, 20, 5, 'R', 9);
        }

        // Line 40
        $pay_suspense_payment = $datas['bottom']['IncomeTaxesPayable']['pay_suspense_payment'];
        if (!empty($pay_suspense_payment)) {
            $this->_putBaseNumber($pdf, $font, $pay_suspense_payment, $x_col6, $y34, 20, 5, 'R', 9);
        }

        // Line 41
        $decrease_sum = $datas['bottom']['decrease_sum'];
        if (!empty($decrease_sum)) {
            $this->_putBaseNumber($pdf, $font, $decrease_sum, $x_col6, $y35, 20, 5, 'R', 9);
        }

        // Line 42
        $end_sum = $datas['bottom']['end_sum'];
        if (!empty($end_sum)) {
            $pdf->SetAutoPageBreak(FALSE);
            $this->_putBaseNumber($pdf, $font, $end_sum, $x_col6, $y36, 20, 5, 'R', 9);
        }

        return $pdf;
    }

    /**
     * put date in export pdf schedules0502s
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param string $date1
     * @param string $date2
     * @param int $y
     */
    function _putDateSchedules0502s($pdf, $font, $date1, $date2, $y) {
        if (!empty($date1)) {
            $y1 = date('Y', strtotime($date1)) - 1988;
            $m1 = date('n', strtotime($date1));
            $d1 = date('j', strtotime($date1));

            $pdf->SetFont($font, null, 9, true);
            $pdf->SetXY(32.4, $y);
            $pdf->MultiCell(10, 5, $y1, 0, 'C');
            $pdf->SetXY(41.4, $y);
            $pdf->MultiCell(10, 5, $m1, 0, 'C');
            $pdf->SetXY(49, $y);
            $pdf->MultiCell(10, 5, $d1, 0, 'C');
        }

        if (!empty($date2)) {
            $y2 = date('Y', strtotime($date2)) - 1988;
            $m2 = date('n', strtotime($date2));
            $d2 = date('j', strtotime($date2));

            $pdf->SetFont($font, null, 9, true);
            $pdf->SetXY(32.4, $y + 3);
            $pdf->MultiCell(10, 5, $y2, 0, 'C');
            $pdf->SetXY(41.4, $y + 3);
            $pdf->MultiCell(10, 5, $m2, 0, 'C');
            $pdf->SetXY(49, $y + 3);
            $pdf->MultiCell(10, 5, $d2, 0, 'C');
        }
    }

    /**
     * Put number to cell
     *
     * @param FPDI OBJ $pdf
     * @param type $item
     * @param type $x
     * @param type $y
     * @param type $margin
     * @param type $align
     */
    function _putNumberItem(&$pdf, $item, $x, $y, $align='R', $margin=null, $check_zero = false)
    {
        if (!isset($item)) return;
		if (empty($item) && !$check_zero) return;

		$item = number_format($item);
		$leng = strlen($item);
		$first_val = substr($item, 0, 1);
		if ($first_val == '-') {
			$first_val = '△';
			$last_val  = substr($item, 1, $leng - 1);
			$item = $first_val . $last_val;
		}
		$pdf->SetXY($x, $y);
		$pdf->MultiCell(28, 5, $item, 0, $align);
    }

    /**
     * Put date to cell
     *
     * @param FPDI OBJ $pdf
     * @param type $src
     * @param type $x
     * @param type $height
     */
    function _putDateConvGtJDate(&$pdf, $src, $x, $height, $font)
    {
        $year  = date('Y', strtotime($src));
        $month = date('n', strtotime($src));
        $day   = date('j', strtotime($src));
//        list($year, $month, $day) = explode('-', $src);
        $date = str_replace('-', '', $src);
        if ($date >= 19890108) {
            $gengo  = '平成';
            $wayear = $year - 1988;
        } elseif ($date >= 19261225) {
            $gengo  = '昭和';
            $wayear = $year - 1925;
        } elseif ($date >= 19120730) {
            $gengo  = '大正';
            $wayear = $year - 1911;
        } else {
            $gengo  = '明治';
            $wayear = $year - 1868;
        }
        //Set x y month day
        $pdf->SetXY($x + 2.5, $height + 0.7);
        $pdf->MultiCell(20, 5, $wayear, 0, 'C');
        $pdf->SetXY($x + 10.0, $height + 0.7);
        $pdf->MultiCell(20, 5, $month, 0, 'C');
        $pdf->SetFont($font, null, 14, true);

        if ($gengo == '昭和') {
            $pdf->SetXY($x - 8.8, $height - 2.5);
            $pdf->MultiCell(28, 2, '◯', 0, 'C');
        }
        if ($gengo == '平成') {
            $pdf->SetXY($x - 8.8, $height + 1.6);
            $pdf->MultiCell(28, 2, '◯', 0, 'C');
        }
    }

    /**
     * Put data into schedules4
     *
     * @param FPDI OBJ $pdf
     * @param type $font
     * @param type $datas
     */
    function putDataSchedules4s(&$pdf, $font, $datas)
    {
        $Term = ClassRegistry::init('Term');

        $term_info = $Term->getCurrentTerm();
        $target_day29 ='2017/04/01';
        //Set XY
        $x_row_1  = 86.9;
        $x_row_2  = 120.0;
        $x_row_3  = 168.4;
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $y_start  = 43.5;
        } else {
          $y_start  = 42.2;
        }
        $step_row = 5.4;

        //user_name
        $user    = CakeSession::read('Auth.User');
        $term_id = $user['term_id'];
        $pdf->SetFont($font, null, 8.5, true);
        $user_name = $user['name'];
        $user_name = $this->roundLineStrByWidth($user_name, 30);
        $x_user_name = 152.0;
        $height = (mb_strwidth($user_name, 'utf8') <= 30) ? 12.8 : 11.5;
        $align  = (mb_strwidth($user_name, 'utf8') <= 30) ? 'C' : 'L';
        $pdf->SetXY($x_user_name, $height);
        $pdf->MultiCell(48, 5, $user_name, 0, $align);


        $pdf->SetFont($font, null, 9, true);
        $account_beggining = $term_info['Term']['account_beggining'];
        $height = 9.6;
        $account_beggining_x = 123;
        $date_margin = array(-4.8, -6.0, -7);
        $this->putHeiseiDate($pdf, $height + 0.4, $account_beggining_x, $account_beggining, $date_margin, true);

        //Term.account_end
        $account_end = $term_info['Term']['account_end'];
        $height += 5.6;
        $this->putHeiseiDate($pdf, $height + 0.3, $account_beggining_x, $account_end, $date_margin, true);

        //main.Schedules4.toukirieki
        $pdf->SetFont($font, null, 8, true);
        $toukirieki = $datas['main']['Schedules4']['toukirieki'];
        $this->_putNumberItem($pdf, $toukirieki, $x_row_1, $y_start);

        //main.Schedules4.box1In
        $box1In = $datas['main']['Schedules4']['box1In'];
        $this->_putNumberItem($pdf, $box1In, $x_row_2, $y_start);

        //main.Schedules4.sonkinkeiri_houjinzei
        $sonkinkeiri_houjinzei = $datas['main']['Schedules4']['sonkinkeiri_houjinzei'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height = $y_start + 7.8;
        } else {
          $height = $y_start + 8.2;
        }
        $this->_putNumberItem($pdf, $sonkinkeiri_houjinzei, $x_row_1, $height);
        $this->_putNumberItem($pdf, $sonkinkeiri_houjinzei, $x_row_2, $height);

        //main.Schedules4.sonkinkeiri_chihouzei
        $sonkinkeiri_chihouzei = $datas['main']['Schedules4']['sonkinkeiri_chihouzei'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += 5.4;
        } else {
          $height += 5.6;
        }
        $this->_putNumberItem($pdf, $sonkinkeiri_chihouzei, $x_row_1, $height);
        $this->_putNumberItem($pdf, $sonkinkeiri_chihouzei, $x_row_2, $height);

        //main.Schedules4.sonkinkeiri_risiwari
        $sonkinkeiri_risiwari = $datas['main']['Schedules4']['sonkinkeiri_risiwari'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height -= 0.2;
        } else {
          $height += 5.2;
        }
        $this->_putNumberItem($pdf, $sonkinkeiri_risiwari, $x_row_1, $height);
        $this->_putNumberItem($pdf, $sonkinkeiri_risiwari, $x_row_2, $height);

        //main.Schedules4.sonkinkeiri_nouzeijyuutoukin
        $sonkinkeiri_nouzeijyuutoukin = $datas['main']['Schedules4']['sonkinkeiri_nouzeijyuutoukin'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $sonkinkeiri_nouzeijyuutoukin, $x_row_1, $height);
        $this->_putNumberItem($pdf, $sonkinkeiri_nouzeijyuutoukin, $x_row_2, $height);

        //main.Schedules4.sonkinkeiri_kasanzei_etc
        $sonkinkeiri_kasanzei_etc = $datas['main']['Schedules4']['sonkinkeiri_kasanzei_etc'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $sonkinkeiri_kasanzei_etc, $x_row_1, $height);
        $this->_putNumberItem($pdf, $sonkinkeiri_kasanzei_etc, $x_row_3, $height);

        //main.Schedules4.depreciation_plus
        $depreciation_plus = $datas['main']['Schedules4']['depreciation_plus'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += $step_row-0.3;
        } else {
          $height += $step_row;
        }
        $this->_putNumberItem($pdf, $depreciation_plus, $x_row_1, $height);
        $this->_putNumberItem($pdf, $depreciation_plus, $x_row_2, $height);

        //main.Schedules4.yakuinnkyuuyo_sonkinhusannyu
        $yakuinnkyuuyo_sonkinhusannyu = $datas['main']['Schedules4']['yakuinnkyuuyo_sonkinhusannyu'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += $step_row-0.3;
        } else {
          $height += $step_row;
        }
        $this->_putNumberItem($pdf, $yakuinnkyuuyo_sonkinhusannyu, $x_row_1, $height);
        $this->_putNumberItem($pdf, $yakuinnkyuuyo_sonkinhusannyu, $x_row_3, $height);

        //main.Schedules4.kousaihi_sonkinfusannyu
        $kousaihi_sonkinfusannyu = $datas['main']['Schedules4']['kousaihi_sonkinfusannyu'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += $step_row-0.3;
        } else {
          $height += $step_row;
        }
        $this->_putNumberItem($pdf, $kousaihi_sonkinfusannyu, $x_row_1, $height);
        $this->_putNumberItem($pdf, $kousaihi_sonkinfusannyu, $x_row_3, $height);

        //main.Schedules4.plusOutSum
        $plusOutSum = $datas['main']['Schedules4']['plusOutSum'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $plusOutSum, $x_row_3, $height + ($step_row +0.95) * 5);
        } else {
          $this->_putNumberItem($pdf, $plusOutSum, $x_row_3, $height + $step_row * 5);
        }

        //main.Schedules4.plusInSum
        $plusInSum = $datas['main']['Schedules4']['plusInSum'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += ($step_row + 0.95) * 5;
        } else {
          $height += $step_row * 5;
        }
        $this->_putNumberItem($pdf, $plusInSum, $x_row_2, $height);

        //main.Schedules4.plusSum
        $plusSum = $datas['main']['Schedules4']['plusSum'];
        $this->_putNumberItem($pdf, $plusSum, $x_row_1, $height);

        //main.Schedules4.depreciation_minus
        $depreciation_minus = $datas['main']['Schedules4']['depreciation_minus'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $depreciation_minus, $x_row_1, $height);
        $this->_putNumberItem($pdf, $depreciation_minus, $x_row_2, $height);

        //main.Schedules4.nouzeijyutoukin_jigyouzei
        $nouzeijyutoukin_jigyouzei = $datas['main']['Schedules4']['nouzeijyutoukin_jigyouzei'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $nouzeijyutoukin_jigyouzei, $x_row_1, $height);
        $this->_putNumberItem($pdf, $nouzeijyutoukin_jigyouzei, $x_row_2, $height);

        //main.Schedules4.ukehai_fusannyu
        $ukehai_fusannyu = $datas['main']['Schedules4']['ukehai_fusannyu'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $ukehai_fusannyu, $x_row_1, $height);
        $this->_putNumberItem($pdf, $ukehai_fusannyu, $x_row_3, $height);

        //main.Schedules4.houjinzeitounokanpu
        $houjinzeitounokanpu = $datas['main']['Schedules4']['houjinzeitounokanpu'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += ($step_row - 0.2) * 4;
        } else {
          $height += $step_row * 4;
        }
        $this->_putNumberItem($pdf, $houjinzeitounokanpu, $x_row_1, $height);
        $this->_putNumberItem($pdf, $houjinzeitounokanpu, $x_row_2, $height);

        //main.Schedules4.shotokuzeitounokanpu
        $shotokuzeitounokanpu = $datas['main']['Schedules4']['shotokuzeitounokanpu'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $shotokuzeitounokanpu, $x_row_1, $height);
        $this->_putNumberItem($pdf, $shotokuzeitounokanpu, $x_row_3, $height);

        //main.Schedules4.minusInSum
        $minusInSum = $datas['main']['Schedules4']['minusInSum'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += ($step_row - 0.15) * 5;
        } else {
          $height += $step_row * 5;
        }
        $this->_putNumberItem($pdf, $minusInSum, $x_row_2, $height, 'R', null, true);

        //main.Schedules4.minusSum
        $minusSum = $datas['main']['Schedules4']['minusSum'];
		      $this->_putNumberItem($pdf, $minusSum, $x_row_1, $height, 'R', null, true);

        //main.Schedules4.preSumIn
        $preSumIn = $datas['main']['Schedules4']['preSumIn'];
        $height += $step_row;
    		$this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height, 'R', null, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
    		  $this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height -0.8 + $step_row * 3, 'R', null, true);
        } else {
          $this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height + $step_row * 3, 'R', null, true);
        }

        //main.Schedules4.preSum
        $preSum = $datas['main']['Schedules4']['preSum'];
        $this->_putNumberItem($pdf, $preSum, $x_row_1, $height , 'R', null, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
		      $this->_putNumberItem($pdf, $preSum, $x_row_1, $height -0.8 + $step_row * 3, 'R', null, true);
        } else {
          $this->_putNumberItem($pdf, $preSum, $x_row_1, $height + $step_row * 3, 'R', null, true);
        }

        //main.Schedules4.kifukin_sonkinhusannyu
        $kifukin_sonkinhusannyu = $datas['main']['Schedules4']['kifukin_sonkinhusannyu'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += ($step_row -0.2) * 4;
        } else {
          $height += $step_row * 4;
        }
        $this->_putNumberItem($pdf, $kifukin_sonkinhusannyu, $x_row_1, $height);
        $this->_putNumberItem($pdf, $kifukin_sonkinhusannyu, $x_row_3, $height);

        //main.Schedules4.shotokuzeigakukoujyo
        $shotokuzeigakukoujyo = $datas['main']['Schedules4']['shotokuzeigakukoujyo'];
        $height += $step_row;
        $this->_putNumberItem($pdf, $shotokuzeigakukoujyo, $x_row_1, $height);
        $this->_putNumberItem($pdf, $shotokuzeigakukoujyo, $x_row_3, $height);

        //main.Schedules4.Sum
        $Sum = $datas['main']['Schedules4']['Sum'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += ($step_row-0.2) * 2;
        } else {
          $height += $step_row * 2;
        }
    		$this->_putNumberItem($pdf, $Sum, $x_row_1, $height, 'R', null, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
    		  $this->_putNumberItem($pdf, $Sum, $x_row_1, $height + $step_row+5.2 * 3, 'R', null, true);
        } else {
          $this->_putNumberItem($pdf, $Sum, $x_row_1, $height + $step_row * 3, 'R', null, true);
        }


        //main.Schedules4.preSumIn
        $preSumIn = $datas['main']['Schedules4']['preSumIn'];
        $this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height, 'R', null, true);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
      		$this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height+4.8 + $step_row * 3, 'R', null, true);
        } else {
      		$this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height + $step_row * 3, 'R', null, true);
        }
        //main.Schedules4.preSumkessonkin_toukikoujyoIn
        $kessonkin_toukikoujyo = $datas['main']['Schedules4']['kessonkin_toukikoujyo'];
        $height += $step_row * 4;
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $kessonkin_toukikoujyo, $x_row_1, $height+4.6);
          $this->_putNumberItem($pdf, $kessonkin_toukikoujyo, $x_row_3, $height+4.6);
        } else {
          $this->_putNumberItem($pdf, $kessonkin_toukikoujyo, $x_row_1, $height);
          $this->_putNumberItem($pdf, $kessonkin_toukikoujyo, $x_row_3, $height);
        }

        //main.Schedules4.shotoku
        $shotoku = $datas['main']['Schedules4']['shotoku'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height += $step_row+ 3.75;
        } else {
          $height += $step_row;
        }
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
    		    $this->_putNumberItem($pdf, $shotoku, $x_row_1, $height + 0.5, 'R', null, true);
         } else {
           $this->_putNumberItem($pdf, $shotoku, $x_row_1, $height, 'R', null, true);
         }
    		$this->_putNumberItem($pdf, $shotoku, $x_row_1, $height + $step_row * 3, 'R', null, true);

        //main.Schedules4.haito
        $haito = $datas['main']['Schedules4']['haito'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $haito, $x_row_3, 40+1);
        } else {
          $this->_putNumberItem($pdf, $haito, $x_row_3, 40);
        }

        //main.Schedules4.sonota
        $sonota = $datas['main']['Schedules4']['sonota'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $sonota, $x_row_3, 40 + $step_row+1);
        } else {
          $this->_putNumberItem($pdf, $sonota, $x_row_3, 40 + $step_row);
        }

        //main.Schedules4.minusOther
        //$pdf->SetFont($font, null, 6.5, true);
        $minusOther = (int)($datas['main']['Schedules4']['minusOther']);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $minusOther, $x_row_3, 181.5);
          $this->_putNumberItem($pdf, $minusOther * (-1), $x_row_3, 181.4 + $step_row);
          $height_top = 181.1 + $step_row * 4;
          $this->_putNumberItem($pdf, $minusOther * (-1), $x_row_3, $height_top);
        } else {
          $this->_putNumberItem($pdf, $minusOther, $x_row_3, 184.4);
          $this->_putNumberItem($pdf, $minusOther * (-1), $x_row_3, 184.4 + $step_row);
          $height_top = 184.4 + $step_row * 4;
          $this->_putNumberItem($pdf, $minusOther * (-1), $x_row_3, $height_top);
        }

        //main.Schedules4.minusOutSum
        $minusOutSum = $datas['main']['Schedules4']['minusOutSum'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $minusOutSum, $x_row_3, 183.5);
        } else {
          $this->_putNumberItem($pdf, $minusOutSum, $x_row_3, 186.9);
        }

        //main.Schedule4.preSumOut
        $preSumOut = $datas['main']['Schedules4']['preSumOut'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $this->_putNumberItem($pdf, $preSumOut, $x_row_3, 183.8 + $step_row);
          $height_bottom = 183.4 + $step_row * 4;
        } else {
          $this->_putNumberItem($pdf, $preSumOut, $x_row_3, 186.9 + $step_row);
          $height_bottom = 186.9 + $step_row * 4;
        }
        $this->_putNumberItem($pdf, $preSumOut, $x_row_3, $height_bottom);

        //main.Schedule4.minusOther
        $minusOther = (int)($datas['main']['Schedules4']['minusOther']);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height_top += ($step_row-0.2) * 4;
        } else {
          $height_top += $step_row * 4;
        }
        $this->_putNumberItem($pdf, $minusOther * (-1), $x_row_3, $height_top);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height_top += ($step_row+1.7) * 3;
        } else {
          $height_top += $step_row * 3;
        }
        $this->_putNumberItem($pdf, $minusOther * (-1), $x_row_3, $height_top);

        //main.Schedule4.SumOut
        $SumOut = $datas['main']['Schedules4']['SumOut'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height_bottom += $step_row * 4;
          $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom-0.7);
          $height_bottom += $step_row * 3;
          $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom+4.3);
          $height_bottom += $step_row * 2;
          $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom+4.1);
        } else {
          $height_bottom += $step_row * 4;
          $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom);
          $height_bottom += $step_row * 3;
          $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom);
          $height_bottom += $step_row * 2;
          $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom);
        }

        //main.Schedule4.shotokuOther TODO:
        $shotokuOther = $datas['main']['Schedules4']['shotokuOther'];
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height_top += $step_row * 2;
          $this->_putNumberItem($pdf, $shotokuOther, $x_row_3, $height_top-0.5);
          $height_top += $step_row * 3;
          $this->_putNumberItem($pdf, $shotokuOther, $x_row_3, $height_top-1);
        } else {
          $height_top += $step_row * 2;
          $this->_putNumberItem($pdf, $shotokuOther, $x_row_3, $height_top);
          $height_top += $step_row * 3;
          $this->_putNumberItem($pdf, $shotokuOther, $x_row_3, $height_top);
        }

        //main.Schedules4.preSumIn
        $preSumIn = $datas['main']['Schedules4']['preSumIn'];
    		$this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height, 'R', null, true);
    		$this->_putNumberItem($pdf, $preSumIn, $x_row_2, $height + $step_row * 3, 'R', null, true);

        $pdf->SetAutoPageBreak(false, 0);
        if(strtotime($target_day29) <= strtotime($term_info['Term']['account_end'])){
          $height_bottom += $step_row * 3+3.5;
        } else {
          $height_bottom += $step_row * 3;
        }
        $this->_putNumberItem($pdf, $SumOut, $x_row_3, $height_bottom);
    }

    /**
     * Put data sub plus into schedules4
     *
     * @param FPDI OBJ $pdf
     * @param type $font
     * @param type $datas
     */
    public function putDataSubPlus4s(&$pdf, $font, $datas){

      $Term = ClassRegistry::init('Term');

      //Term.account_beggining
      $term = $Term->getCurrentTerm();

      $target_day29 ='2017/04/01';

        //Set XY
        $x_row_0   = 44.8;
        $x_row_1   = 86.9;
        $x_row_2   = 120.0;
        $x_row_3   = 168.4;
        if(strtotime($target_day29) <= strtotime($term['Term']['account_end'])){
          $step_row  = 5.2;
          $y_row_cal = 88.5;
        } else {
          $y_row_cal = 93.6;
          $step_row  = 5.4;
        }
        $x_middle  = 143.9;
        foreach ($datas as $val) {
            //CalculationDetail.item_name
            $pdf->SetFont($font, null, 7, true);
            $itemName = h($val['CalculationDetail']['item_name']);
            $pdf->SetXY($x_row_0, $y_row_cal);
            $pdf->MultiCell(36, 5, $itemName, 0, 'L');

            //CalculationDetail.adding
            $pdf->SetFont($font, null, 8, true);
            $adding = $val['CalculationDetail']['adding'];
            $this->_putNumberItem($pdf, $adding, $x_row_1, $y_row_cal);

            //CalculationDetail.cal_in
            $cal_in = $val['CalculationDetail']['cal_in'];
            $this->_putNumberItem($pdf, $cal_in, $x_row_2, $y_row_cal);

            //CalculationDetail.cal_class
            $cal_class = h($val['CalculationDetail']['cal_class']);
            $pdf->SetXY(143.9, $y_row_cal-0.5);
            $pdf->MultiCell(28, 5, $cal_class, 0, 'C');

            //CalculationDetail.cal_out
            $cal_out = $val['CalculationDetail']['cal_out'];
            $this->_putNumberItem($pdf, $cal_out, $x_row_3, $y_row_cal);

            $y_row_cal += $step_row;
        }
    }

    /**
     * Put data sub minus into schedules4
     *
     * @param FPDI OBJ $pdf
     * @param type $font
     * @param type $datas
     */
    public function putDataSubMinus4s(&$pdf, $font, $datas)
    {
      $Term = ClassRegistry::init('Term');

      //Term.account_beggining
      $term = $Term->getCurrentTerm();

      $target_day29 ='2017/04/01';

        //Set XY
        $x_row_0   = 44.8;
        $x_row_1   = 86.9;
        $x_row_2   = 120.0;
        $x_row_3   = 168.4;
        if(strtotime($target_day29) <= strtotime($term['Term']['account_end'])){
          $y_row_cal = 92.8;
          $step_row  = 5.3;
        } else {
          $y_row_cal = 93.6;
          $step_row  = 5.4;
        }

        $x_middle  = 143.9;
        foreach ($datas as $val) {
            //CalculationDetail.item_name
            $pdf->SetFont($font, null, 7, true);
            $itemName = h($val['CalculationDetail']['item_name']);
            $pdf->SetXY($x_row_0, $y_row_cal + $step_row * 13);
            $pdf->MultiCell(36, 5, $itemName, 0, 'L');

            //CalculationDetail.subtraction
            $pdf->SetFont($font, null, 8, true);
            $subtraction = $val['CalculationDetail']['subtraction'];
            $this->_putNumberItem($pdf, $subtraction, $x_row_1, $y_row_cal + $step_row * 13);

            //CalculationDetail.cal_in
            $cal_in = $val['CalculationDetail']['cal_in'];
            $this->_putNumberItem($pdf, $cal_in, $x_row_2, $y_row_cal + $step_row * 13);

            //CalculationDetail.cal_class
            $cal_class = h($val['CalculationDetail']['cal_class']);
            $pdf->SetXY(143.9, $y_row_cal + $step_row * 13);
            $pdf->MultiCell(28, 5, $cal_class, 0, 'C');

            //CalculationDetail.cal_out
            $pdf->SetFont($font, null, 7, true);
            $cal_out = $val['CalculationDetail']['cal_out'];
            $this->_putNumberItem($pdf, $cal_out, $x_row_3, $y_row_cal + $step_row * 13);

            $y_row_cal += $step_row;
        }
    }

    /**
     * Set add page schedules4
     *
     * @param FPDI $pdf
     * @param TCPDF_FONTS $font
     * @param FPDI $template
     * @param array $data_sub_plus
     * @param array $data_sub_minus
     * @param array $datas
     * @param boolean $first
     */
    function addPageSchedules4(&$pdf, $font, $template, $data_sub_plus, $data_sub_minus, $datas, $first=false)
    {
      $size_of_sub_plus  = sizeof($data_sub_plus);
      $size_of_sub_minus = sizeof($data_sub_minus);

      $Term = ClassRegistry::init('Term');
      $target_day29 ='2017/04/01';

      //Term.account_beggining
      $term = $Term->getCurrentTerm();

      if(strtotime($target_day29) <= strtotime($term['Term']['account_end'])){

        if ($size_of_sub_plus <= 5 && $size_of_sub_minus <= 4) {
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus);
        } else if($size_of_sub_plus > 5 && $size_of_sub_minus <= 4) {
            $data_sub_plus_1 = array_slice($data_sub_plus, 0, 5);
            $data_sub_plus_2 = array_slice($data_sub_plus, 5);
            $data_sub_plus_1 = array_slice($data_sub_plus, 0, 5);
            $data_sub_plus_2 = array_slice($data_sub_plus, 5);
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus_1);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus);
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus_2);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus);
        } else if($size_of_sub_plus <= 5 && $size_of_sub_minus > 4) {
            $data_sub_minus_1 = array_slice($data_sub_minus, 0, 4);
            $data_sub_minus_2 = array_slice($data_sub_minus, 4);
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus_1);
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus_2);
        } else {
            $data_sub_plus_1  = array_slice($data_sub_plus, 0, 5);
            $data_sub_plus_2  = array_slice($data_sub_plus, 5);
            $data_sub_minus_1 = array_slice($data_sub_minus, 0, 4);
            $data_sub_minus_2 = array_slice($data_sub_minus, 4);
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus_1);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus_1);
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->addPageSchedules4($pdf, $font, $template, $data_sub_plus_2, $data_sub_minus_2, $datas);
        }
      } else {
        if ($size_of_sub_plus <= 4 && $size_of_sub_minus <= 4) {
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus);
        } else if($size_of_sub_plus > 4 && $size_of_sub_minus <= 4) {
            $data_sub_plus_1 = array_slice($data_sub_plus, 0, 4);
            $data_sub_plus_2 = array_slice($data_sub_plus, 4);
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus_1);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus);
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus_2);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus);
        } else if($size_of_sub_plus <= 4 && $size_of_sub_minus > 4) {
            $data_sub_minus_1 = array_slice($data_sub_minus, 0, 4);
            $data_sub_minus_2 = array_slice($data_sub_minus, 4);
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus_1);
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus_2);
        } else {
            $data_sub_plus_1  = array_slice($data_sub_plus, 0, 4);
            $data_sub_plus_2  = array_slice($data_sub_plus, 4);
            $data_sub_minus_1 = array_slice($data_sub_minus, 0, 4);
            $data_sub_minus_2 = array_slice($data_sub_minus, 4);
            if (empty($first)) {
                $this->putDataAddPageSchedules4s($pdf, $font, $datas);
            }
            $this->putDataSubPlus4s($pdf, $font, $data_sub_plus_1);
            $this->putDataSubMinus4s($pdf, $font, $data_sub_minus_1);
            $pdf->AddPage();
            $pdf->useTemplate($template, null, null, null, null, true);
            $this->addPageSchedules4($pdf, $font, $template, $data_sub_plus_2, $data_sub_minus_2, $datas);
        }
      }
    }

    /**
     * Put data into when add page schedules4
     *
     * @param FPDI  $pdf
     * @param FPDI $font
     * @param array $datas
     */
    function putDataAddPageSchedules4s(&$pdf, $font, $datas)
    {
        $Term = ClassRegistry::init('Term');
        $target_day29 ='2017/04/01';

        //Set XY
        $x_row_1  = 86.9;
        $x_row_2  = 120.0;
        $x_row_3  = 168.4;
        $y_start  = 42.2;
        $step_row = 5.4;

        //user_name
        $user    = CakeSession::read('Auth.User');
        $term_id = $user['term_id'];
        $pdf->SetFont($font, null, 8.5, true);
        $user_name = $user['name'];
        //$user_name = $this->roundLineStrByWidth($user_name, 30);
        $user_name = substr($user['name'],0,90);
        $x_user_name = 152.0;
        $height = (mb_strwidth($user_name, 'utf8') <= 30) ? 12.8 : 11.5;
        $align  = (mb_strwidth($user_name, 'utf8') <= 30) ? 'C' : 'L';
        $pdf->SetXY($x_user_name, $height);
        $pdf->MultiCell(48, 5, $user_name, 0, $align);

        //Term.account_beggining
        $term = $Term->find('first',array(
            'conditions'=>array('Term.id'=>$term_id,
            )));
        $pdf->SetFont($font, null, 9, true);
        $account_beggining = $term['Term']['account_beggining'];
        $height = 9.6;
        $account_beggining_x = 123;
        $date_margin = array(-4.8, -6.0, -7);
        $this->putHeiseiDate($pdf, $height + 0.4, $account_beggining_x, $account_beggining, $date_margin, true);

        //Term.account_end
        $account_end = $term['Term']['account_end'];
        $height += 5.6;
        $this->putHeiseiDate($pdf, $height + 0.3, $account_beggining_x, $account_end, $date_margin, true);

        //main.Schedules4.plusInSum
        $pdf->SetFont($font, null, 7, true);
        $plusInSum = $datas['main']['Schedules4']['plusInSum'];
        if(strtotime($target_day29) <= strtotime($term['Term']['account_end'])){
          $height = 114.5;
        } else {
          $height = 115.2;
        }

        $this->_putNumberItem($pdf, $plusInSum, $x_row_2, $height);

        //main.Schedules4.plusSum
        $plusSum = $datas['main']['Schedules4']['plusSum'];
        $this->_putNumberItem($pdf, $plusSum, $x_row_1, $height);

        //main.Schedules4.plusOutSum
        $plusOutSum = $datas['main']['Schedules4']['plusOutSum'];
        $this->_putNumberItem($pdf, $plusOutSum, $x_row_3, $height);


        //main.Schedules4.minusInSum
        $minusInSum = $datas['main']['Schedules4']['minusInSum'];
        if(strtotime($target_day29) <= strtotime($term['Term']['account_end'])){
          $height = 183;
        } else {
          $height = 185.4;
        }
        $this->_putNumberItem($pdf, $minusInSum, $x_row_2, $height);

        //main.Schedules4.minusSum
        $minusSum = $datas['main']['Schedules4']['minusSum'];
        $this->_putNumberItem($pdf, $minusSum, $x_row_1, $height);

        //main.Schedules4.minusOther
        $minusOther = (int)($datas['main']['Schedules4']['minusOther']);
        $this->_putNumberItem($pdf, $minusOther, $x_row_3, $height-1.7);

        //main.Schedules4.minusOutSum
        $minusOutSum = $datas['main']['Schedules4']['minusOutSum'];
        $this->_putNumberItem($pdf, $minusOutSum, $x_row_3, $height + 0.8);


    }

    /**
     * 領収書PDF出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_recepts($pdf, $font) {

      $user    = CakeSession::read('Auth.User');

      if($user['plan'] == 4){
        $template = $this->setTemplateAddPage($pdf, $font, 'receipt.pdf');
      } else if($user['plan'] == 5){
        $template = $this->setTemplateAddPage($pdf, $font, 'receipt_jyoto.pdf');
      } else {
        $template = $this->setTemplateAddPage($pdf, $font, 'receipt_depreciation.pdf');
      }

        $model = ClassRegistry::init('AccountInfo');

        $datas = $model->findForReceipt();

        $point_start_y = 37;      // 出力開始位置起点(縦)
        $point_step = 15.5;          // 次の出力
        //$point_y = $height = 22.5;  // 出力開始位置(縦)
        $point_y = $height = 18;  // 出力開始位置(縦)

        //フォント設定
        $font = new TCPDF_FONTS();
        $ipag = $font->addTTFfont(APP. 'Vendor'. DS. 'tcpdf'. DS. 'fonts'. DS. 'ipag.ttf');

        //色指定
        $pdf->SetTextColor(0, 0, 0);

        // 法人名
        $pdf->SetFont($ipag, null, 14, true);
        $user_name = CakeSession::read('Auth.User.name');
        if($user['plan'] == 4){
          $user_name = $user_name.'　御中';
        } else {
          $user_name = $user_name.'　様';
        }

        $pdf->SetXY(20, 50);
        $pdf->MultiCell(170, 5, $user_name, 0, 'L');

        //金額
        $pdf->SetFont($ipag, null, 18, true);
        $price = number_format($datas['price']);
        $user_name = '¥ '.$price.' -';

        $pdf->SetXY(90, 67.5);
        $pdf->MultiCell(0, 5, $user_name, 0, 'L');

        //領収日
        $pdf->SetFont($ipag, null, 11, true);
        $receipt_day = $datas['AccountInfo']['receipt_day'];

        $pdf->SetXY(163, 42);
        $pdf->MultiCell(29, 5, $receipt_day, 0, 'R');

        //No.
        $pdf->SetFont($ipag, null, 11, true);
        if($user['plan'] == 4){
          $number = $datas['AccountInfo']['id'];
        } else if($user['plan'] == 5){
          $number = $datas['jyoto_id'];
        }
        $year = date('Y');
        $number = $year.'7'.$number;

        $pdf->SetXY(163, 36);
        $pdf->MultiCell(29, 5, $number, 0, 'R');


        return $pdf;
    }

    /**
     * 譲渡の内訳書PDF出力 -　1面
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto1($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'h28_jyoto_uchiwakesho1.pdf');
        $User       = ClassRegistry::init('User');
        $realEstate = $User->findForRealEstateAccount();

        // 年
        $pdf->SetFont($font, null, 15, true);
        $pdf->SetXY(151, 23);
        $pdf->MultiCell(0, 1, $realEstate['RealEstate']['for_japanese_year'], 0, 'C');

        // 提出枚数
        $pdf->SetFont($font, null, 12, true);
        $pdf->SetXY(122.5, 43);
        $pdf->MultiCell(0, 1, 1, 0, 'C');
        $pdf->SetXY(174.5, 43);
        $pdf->MultiCell(0, 1, 1, 0, 'C');

        // 現住所
        $pdf->SetFont($font, null, 11, true);
        $address = h($realEstate['NameList']['prefecture'] . $realEstate['NameList']['city'] . $realEstate['NameList']['address']);
        $height = 94;
        if ($address) {
            if (40 < mb_strwidth($address, 'utf8')) {
                $pdf->SetFont($font, null, 10, true);
            }
            $address = $this->roundLineStrByWidthNot($address, 44);
            $height = (mb_strwidth($address, 'utf8') <= 44) ? $height + 2 : $height;
            $pdf->SetXY(43.3, $height);
            $pdf->MultiCell(80, 2, $address, 0, 'L');
        }

        // 前住所
        $pdf->SetFont($font, null, 9, true);
        $address = h($realEstate['RealEstate']['pre_prefecture'] . $realEstate['RealEstate']['pre_city'] . $realEstate['RealEstate']['pre_address']);
        $height = 101.8;
        if ($address) {
            if (44 < mb_strwidth($address, 'utf8')) {
                $pdf->SetFont($font, null, 8, true);
            }
            $address = $this->roundLineStrByWidthNot($address, 49);
            $height = (mb_strwidth($address, 'utf8') <= 49) ? $height + 0.7 : $height;
            $pdf->SetXY(47, $height);
            $pdf->MultiCell(72, 0, $address, 0, 'L');
        }

        // 電話番号
        $phoneNumber = $realEstate['NameList']['phone_number'];
        if ($phoneNumber) {
            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(43.3, 111.5);
            $pdf->MultiCell(80, 2, $phoneNumber, 0, 'L');
        }

        // フリガナ
        $furigana = $realEstate['NameList']['name_furigana'];
        if ($furigana) {
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(138.5, 96.3);
            $furigana = $this->roundLineStrByWidthNot($furigana, 40, 1);
            $pdf->MultiCell(60, 0, $furigana, 0, 'L');
        }

        // 氏名
        $name = $realEstate['User']['name'];
        if ($name) {
            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(138.5, 101.5);
            $name = $this->roundLineStrByWidthNot($name, 29, 1);
            $pdf->MultiCell(60, 0, $name, 0, 'L');
        }

        // 職業
        $job = $realEstate['User']['job'];
        if ($job) {
            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(138.5, 111.5);
            $job = $this->roundLineStrByWidthNot($job, 29, 1);
            $pdf->MultiCell(60, 0, $job, 0, 'L');
        }

        // 関与税理士名
        $taxAccountant = $realEstate['User']['tax_accountant_name'];
        if ($taxAccountant) {
            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(125, 135);
            $taxAccountant = $this->roundLineStrByWidthNot($taxAccountant, 36, 1);
            $pdf->MultiCell(73, 0, $taxAccountant, 0, 'L');
        }
        // 関与税理士 電話番号
        $tel = $realEstate['User']['tax_accountant_phone'];
        if ($tel) {
            $pdf->SetFont($font, null, 10, true);
            $pdf->SetXY(156.5, 141.3);
            $pdf->MultiCell(39, 0, $tel, 0, 'C');
        }

        return $pdf;
    }

    /**
     * 譲渡の内訳書PDF出力 -　2面
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto2($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'h28_jyoto_uchiwakesho2.pdf');
        $ReakEstate = ClassRegistry::init('RealEstate');
        $realEstate = $ReakEstate->getForPage2();

        // 所在地番
        $address = h($realEstate['RealEstate']['sale_old_prefecture'] . $realEstate['RealEstate']['sale_old_city'] . $realEstate['RealEstate']['sale_old_address']);
        if ($address) {
            $pdf->SetFont($font, null, 11, true);
            $address = $this->roundLineStrByWidthNot($address, 66);
            $height = (mb_strwidth($address, 'utf8') <= 66) ? 38 : 36;
            $pdf->SetXY(45, $height);
            $pdf->MultiCell(132, 0, $address, 0, 'L');
        }

        // (住居表示)
        $address = h($realEstate['RealEstate']['sale_prefecture'] . $realEstate['RealEstate']['sale_city'] . $realEstate['RealEstate']['sale_address']);
        if ($address) {
            $pdf->SetFont($font, null, 9, true);
            $address = $this->roundLineStrByWidthNot($address, 81);
            $height = (mb_strwidth($address, 'utf8') <= 81) ? 49.5 : 47.5;
            $pdf->SetXY(45, $height);
            $pdf->MultiCell(132, 0, $address, 0, 'L');
        }

        // 土地種類
        $pdf->SetFont($font, 'B', 11, true);
        $this->printCheckMark($pdf, $realEstate['RealEstate']['land_class'], array(
            1 => array(22, 66),
            2 => array(41.6, 74.8),
            3 => array(22, 79.2),
        ));
        if ($realEstate['RealEstate']['land_class'] == 3) {
            // その他
            $text = $realEstate['RealEstate']['land_class_other'];
            $text = mb_strimwidth($text, 0, 14, '..', 'utf-8');
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(38.1, 80.2);
            $pdf->MultiCell(22, 0, $text, 0, 'C');
        }
        // 実測
        $landArea = $realEstate['RealEstate']['land_area'];
        if ($landArea) {
            $pdf->SetFont($font, null, 10, true);
            $pdf->SetXY(63.5, 70);
            $pdf->MultiCell(18, 0, $landArea, 0, 'C');
        }
        // 公道等
        $landAreaPaper = $realEstate['RealEstate']['land_area_paper'];
        if ($landAreaPaper) {
            $pdf->SetFont($font, null, 10, true);
            $pdf->SetXY(63.5, 81);
            $pdf->MultiCell(18, 0, $landAreaPaper, 0, 'C');
        }

        // 建物種類
        $pdf->SetFont($font, 'B', 11, true);
        $this->printCheckMark($pdf, $realEstate['RealEstate']['house_class'], array(
            1 => array(22, 87.6),
            2 => array(41.6, 87.6),
            3 => array(22, 96.3),
        ));
        if ($realEstate['RealEstate']['house_class'] == 3) {
            // その他
            $text = $realEstate['RealEstate']['house_class_other'];
            $text = mb_strimwidth($text, 0, 22, '..', 'utf-8');
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(26.5, 101.7);
            $pdf->MultiCell(34, 0, $text, 0, 'C');
        }
        // 建物面積
        $houseArea = $realEstate['RealEstate']['house_area'];
        if ($houseArea) {
            $pdf->SetFont($font, null, 10, true);
            $pdf->SetXY(63.5, 95);
            $pdf->MultiCell(18, 0, $houseArea, 0, 'C');
        }

        // 利用状況
        $pdf->SetFont($font, 'B', 11, true);
        $this->printCheckMark($pdf, 1, array(
            1 => array(90, 74.3),
        ));
        // 居住期間
        $pdf->SetFont($font, null, 11, true);
        if ($realEstate['RealEstate']['living_start_date']) {
            $startDates = explode('-', $realEstate['RealEstate']['living_start_date']);
            $pdf->SetXY(97, 84);
            $pdf->Cell(0, 0, $startDates[0], 0, 'L');
            $pdf->SetX(109.5);
            $pdf->Cell(0, 0, $startDates[1], 0, 'L');
        }
        if ($realEstate['RealEstate']['living_end_date']) {
            $endDates = explode('-', $realEstate['RealEstate']['living_end_date']);
            $pdf->SetXY(120.5, 84);
            $pdf->Cell(0, 0, $endDates[0], 0, 'L');
            $pdf->SetX(133);
            $pdf->Cell(0, 0, $endDates[1], 0, 'L');
        }

        // 売買契約日
        $pdf->SetFont($font, null, 11, true);
        if ($realEstate['RealEstate']['sale_date']) {
            $dates = explode('-', $realEstate['RealEstate']['sale_date']);
            $pdf->SetXY(149, 79.3);
            $pdf->Cell(0, 0, $dates[0], 0, 'R');
            $pdf->SetX(161.5);
            $pdf->Cell(0, 0, $dates[1], 0, 'R');
            $pdf->SetX(170);
            $pdf->Cell(0, 0, $dates[2], 0, 'R');
        }
        // 引渡し日
        if ($realEstate['RealEstate']['real_sale_date']) {
            $dates = explode('-', $realEstate['RealEstate']['real_sale_date']);
            $pdf->SetXY(149, 101.2);
            $pdf->Cell(0, 0, $dates[0], 0, 'R');
            $pdf->SetX(161.5);
            $pdf->Cell(0, 0, $dates[1], 0, 'R');
            $pdf->SetX(170);
            $pdf->Cell(0, 0, $dates[2], 0, 'R');
        }

        // 持分(上)
        $pdf->SetFont($font, null, 11, true);
        $num = $realEstate['RealEstate']['your_share_land_up'];
        $pdf->SetXY(22, 130);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        $num = $realEstate['RealEstate']['your_share_up'];
        $pdf->SetX(39);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        // 持分(下)
        $num = $realEstate['RealEstate']['your_share_land_down'];
        $pdf->SetXY(22, 141);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        $num = $realEstate['RealEstate']['your_share_down'];
        $pdf->SetX(39);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        // 共有者1の住所
        $address = h($realEstate['RealEstate']['share_prefecture']. $realEstate['RealEstate']['share_city']. $realEstate['RealEstate']['share_address']);
        if ($address) {
            $pdf->SetFont($font, null, 8, true);
            $address = $this->roundLineStrByWidthNot($address, 34);
            $pdf->SetXY(57, 130.5);
            $pdf->MultiCell(50, 0, $address, 0, 'L');
        }
        // 共有者2の住所
        $address = h($realEstate['RealEstate']['share_prefecture2']. $realEstate['RealEstate']['share_city2']. $realEstate['RealEstate']['share_address2']);
        if ($address) {
            $pdf->SetFont($font, null, 8, true);
            $address = $this->roundLineStrByWidthNot($address, 34);
            $pdf->SetXY(57, 142.7);
            $pdf->MultiCell(50, 0, $address, 0, 'L');
        }

        // 共有者1の氏名
        $name = h($realEstate['RealEstate']['share_name']);
        if ($name) {
            $name = $this->roundLineStrByWidthNot($name, 24, 1);
            $pdf->SetFont($font, null, 10, true);
            $pdf->SetXY(105, 130.5);
            $pdf->MultiCell(45, 0, $name, 0, 'C');
        }
        // 共有者2の氏名
        $name = h($realEstate['RealEstate']['share_name2']);
        if ($name) {
            $name = $this->roundLineStrByWidthNot($name, 24, 1);
            $pdf->SetFont($font, null, 10, true);
            $pdf->SetXY(105, 142.7);
            $pdf->MultiCell(45, 0, $name, 0, 'C');
        }

        //共有者が３名以上いた場合
        if($realEstate['RealEstate']['other_share_num']){
          $other_num = '他'.$realEstate['RealEstate']['other_share_num'].'名';
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(102, 138.5);
          $pdf->MultiCell(45, 0, $other_num, 0, 'C');
        }
        // 共有者1の持分 - 土地
        $pdf->SetFont($font, null, 10, true);
        $num = $realEstate['RealEstate']['first_share_land_up'];
        $pdf->SetXY(150, 127);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        $num = $realEstate['RealEstate']['first_share_land_down'];
        $pdf->SetXY(150, 131.7);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        // 共有者1の持分 - 建物
        $num = $realEstate['RealEstate']['first_share_up'];
        $pdf->SetXY(167, 127);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        $num = $realEstate['RealEstate']['first_share_down'];
        $pdf->SetXY(167, 131.7);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        // 共有者2の持分 - 土地
        $pdf->SetFont($font, null, 10, true);
        $num = $realEstate['RealEstate']['other_share_land_up'];
        $pdf->SetXY(150, 139.5);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        $num = $realEstate['RealEstate']['other_share_land_down'];
        $pdf->SetXY(150, 144.2);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        // 共有者2の持分 - 建物
        $num = $realEstate['RealEstate']['other_share_up'];
        $pdf->SetXY(167, 139.5);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        $num = $realEstate['RealEstate']['other_share_down'];
        $pdf->SetXY(167, 144.2);
        $pdf->Cell(15, 0, $num, 0, 0, 'C');

        // 買主住所
        $address = h($realEstate['RealEstate']['buyer_prefecture']. $realEstate['RealEstate']['buyer_city']. $realEstate['RealEstate']['buyer_address']);
        if ($address) {
            $pdf->SetFont($font, null, 9, true);
            $height = (mb_strwidth($address, 'utf8') <= 46) ? 165.2 : 163.5;
            $address = $this->roundLineStrByWidthNot($address, 46);
            $pdf->SetXY(36, $height);
            $pdf->MultiCell(80, 0, $address, 0, 'L');
        }

        // 買主氏名
        $name = h($realEstate['RealEstate']['buyer_name']);
        if ($name) {
            $pdf->SetFont($font, null, 9, true);
            $height = (mb_strwidth($name, 'utf8') <= 22) ? 176.5 : 175;
            $name = $this->roundLineStrByWidthNot($name, 22);
            $pdf->SetXY(36, $height);
            $pdf->MultiCell(38, 0, $name, 0, 'L');
        }

        // 買主職業
        $job = h($realEstate['RealEstate']['buyer_job']);
        if ($job) {
            $pdf->SetFont($font, null, 8, true);
            $job = $this->roundLineStrByWidthNot($job, 15, 1);
            $pdf->SetXY(88.2, 177);
            $pdf->MultiCell(24, 0, $job, 0, 'L');
        }

        // 譲渡価格
        $the_receive_sum = number_format($realEstate['sum']['the_receive_sum']);
        if($realEstate['sum']['share_receive_sum']){
          $realEstate['sum']['receive_sum'] = $realEstate['sum']['share_receive_sum'];
        }
        $receive_sum = number_format($realEstate['sum']['receive_sum']);

        if ($realEstate['sum']['receive_sum'] != $realEstate['sum']['the_receive_sum']) {
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(145, 174);
            $pdf->Cell(33, 0, "({$the_receive_sum}×持分)", 0, 2, 'R');
            $pdf->SetFont($font, null, 12, true);
            $pdf->SetXY(115, 178.2);
            $pdf->MultiCell(64, 0, $receive_sum, 0, 'R');
        } else {
          $pdf->SetFont($font, null, 12, true);
          $pdf->SetXY(115, 178.2);
          $pdf->MultiCell(64, 0, $receive_sum, 0, 'R');
        }

        // 代金の受領状況1
        $date = $realEstate['RealEstate']['receive_date'];
        $x = 36.8;
        if ($date) {
            $pdf->SetFont($font, null, 10, true);
            $dates = explode('-', $date);
            $pdf->SetXY($x, 201.38);
            $pdf->Cell(11, 0, $dates[0], 0, null, 'L');
            $pdf->SetX($x + 10.2);
            $pdf->Cell(8, 0, $dates[1], 0, null, 'C');
            $pdf->SetX($x + 17.7);
            $pdf->Cell(8, 0, $dates[2], 0, null, 'C');
        }
        if ($realEstate['RealEstate']['receive_split_sum']) {
            $price = number_format($realEstate['RealEstate']['receive_split_sum']);
            $pdf->SetXY($x + 0.2, 210);
            $pdf->MultiCell(30, 0, $price, 0, 'R');
        }

        // 代金の受領状況2
        $date = $realEstate['RealEstate']['receive_date2'];
        $x = 71.8;
        if ($date) {
            $pdf->SetFont($font, null, 10, true);
            $dates = explode('-', $date);
            $pdf->SetXY($x, 201.38);
            $pdf->Cell(11, 0, $dates[0], 0, null, 'L');
            $pdf->SetX($x + 10.2);
            $pdf->Cell(8, 0, $dates[1], 0, null, 'C');
            $pdf->SetX($x + 17.7);
            $pdf->Cell(8, 0, $dates[2], 0, null, 'C');
        }
        if ($realEstate['RealEstate']['receive_split_sum2']) {
            $price = number_format($realEstate['RealEstate']['receive_split_sum2']);
            $pdf->SetXY($x + 0.2, 210);
            $pdf->MultiCell(30, 0, $price, 0, 'R');
        }

        // 代金の受領状況3
        $date = $realEstate['RealEstate']['receive_date3'];
        $x = 106.3;
        if ($date) {
            $pdf->SetFont($font, null, 10, true);
            $dates = explode('-', $date);
            $pdf->SetXY($x, 201.38);
            $pdf->Cell(11, 0, $dates[0], 0, null, 'L');
            $pdf->SetX($x + 10.2);
            $pdf->Cell(8, 0, $dates[1], 0, null, 'C');
            $pdf->SetX($x + 17.7);
            $pdf->Cell(8, 0, $dates[2], 0, null, 'C');
        }
        if ($realEstate['RealEstate']['receive_split_sum3']) {
            $price = number_format($realEstate['RealEstate']['receive_split_sum3']);
            $pdf->SetXY($x + 0.2, 210);
            $pdf->MultiCell(30, 0, $price, 0, 'R');
        }

        // 未収金
        $date = $realEstate['RealEstate']['receive_account_date'];
        $x = 139;
        if ($date) {
            $pdf->SetFont($font, null, 10, true);
            $dates = explode('-', $date);
            $pdf->SetXY($x, 201.38);
            $pdf->Cell(11, 0, $dates[0], 0, null, 'L');
            $pdf->SetX($x + 10.2);
            $pdf->Cell(8, 0, $dates[1], 0, null, 'C');
            $pdf->SetX($x + 17.7);
            $pdf->Cell(8, 0, $dates[2], 0, null, 'C');
        }
        if ($realEstate['RealEstate']['next_receive_sum']) {
            $price = number_format($realEstate['RealEstate']['next_receive_sum']);
            $pdf->SetXY($x + 2, 210);
            $pdf->MultiCell(30, 0, $price, 0, 'R');
        }

        // 売った理由
        $pdf->SetFont($font, 'B', 10, true);
        $this->printCheckMark($pdf, $realEstate['RealEstate']['sale_reason'], array(
            1 => array(59, 221.8),
            2 => array(59, 227.2),
            3 => array(59, 232.7),
            4 => array(119.8, 221.8),
            5 => array(119.8, 227.2),
        ));
        if ($realEstate['RealEstate']['sale_reason'] == 5) {
            $text = $realEstate['RealEstate']['sale_reason_other'];
            $pdf->SetFont($font, null, 9, true);
            $text = $this->roundLineStrByWidthNot($text, 28, 1);
            $pdf->SetXY(127.6, 233.3);
            $pdf->MultiCell(47, 0, $text, 0, 'C');
        }

        return $pdf;
    }

    /**
     * 譲渡の内訳書PDF出力 -　3面
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto3($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'h28_jyoto_uchiwakesho3.pdf');
        $ReakEstate = ClassRegistry::init('RealEstate');
        $realEstate = $ReakEstate->findForResultIndex();

        // 土地住所
        $address = h($realEstate['GetRealEstate'][0]['prefecture'] . $realEstate['GetRealEstate'][0]['city'] . $realEstate['GetRealEstate'][0]['address']);
        if ($address) {
            $pdf->SetFont($font, null, 8, true);
            $address = $this->roundLineStrByWidthNot($address, 33);
            $height = (mb_strwidth($address, 'utf8') <= 33) ? 42 : 40.5;
            $pdf->SetXY(51.3, $height);
            $pdf->MultiCell(49, 0, $address, 0, 'L');
        }
        // 土地氏名
        $name = h($realEstate['GetRealEstate'][0]['seller_name']);
        if ($name) {
            $pdf->SetFont($font, null, 8, true);
            $name = $this->roundLineStrByWidthNot($name, 25);
            $height = (mb_strwidth($name, 'utf8') <= 25) ? 42 : 40.5;
            $pdf->SetXY(100, $height);
            $pdf->MultiCell(40, 0, $name, 0, 'L');
        }
        // 購入年月日
        $date = $realEstate['GetRealEstate'][0]['get_date'];
        if ($date) {
            $pdf->SetFont($font, null, 8, true);
            $dates = explode('-', $date);
            $pdf->SetXY(137.9, 42);
            $pdf->Cell(7, 0, $dates[0], 0, 0, 'C');
            $pdf->Cell(7.6, 0, ltrim($dates[1], 0), 0, 0, 'C');
            $pdf->Cell(7, 0, ltrim($dates[2], 0), 0, 0, 'C');
        }
        // 購入金額
        $price = number_format($realEstate['GetRealEstate'][0]['pay_sum']);
        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(158.3, 41.8);
        $pdf->MultiCell(34, 0, $price, 0, 'R');
        $pdf->SetXY(158.3, 67.1);
        $pdf->MultiCell(34, 0, $price, 0, 'R');


        // 建物住所
        $address = h($realEstate['GetRealEstate'][1]['prefecture'] . $realEstate['GetRealEstate'][1]['city'] . $realEstate['GetRealEstate'][1]['address']);
        if ($address) {
            $pdf->SetFont($font, null, 8, true);
            $address = $this->roundLineStrByWidthNot($address, 33);
            $height = (mb_strwidth($address, 'utf8') <= 33) ? 76.1 : 74.5;
            $pdf->SetXY(51.3, $height);
            $pdf->MultiCell(49, 0, $address, 0, 'L');
        }
        // 建物氏名
        $name = h($realEstate['GetRealEstate'][1]['seller_name']);
        if ($name) {
            $pdf->SetFont($font, null, 8, true);
            $name = $this->roundLineStrByWidthNot($name, 25);
            $height = (mb_strwidth($name, 'utf8') <= 25) ? 76.1 : 74.5;
            $pdf->SetXY(100, $height);
            $pdf->MultiCell(40, 0, $name, 0, 'L');
        }
        // 建物購入年月日
        $date = $realEstate['GetRealEstate'][1]['get_date'];
        if ($date) {
            $pdf->SetFont($font, null, 8, true);
            $dates = explode('-', $date);
            $pdf->SetXY(137.9, 76.1);
            $pdf->Cell(7, 0, $dates[0], 0, 0, 'C');
            $pdf->Cell(7.6, 0, ltrim($dates[1], 0), 0, 0, 'C');
            $pdf->Cell(7, 0, ltrim($dates[2], 0), 0, 0, 'C');
        }
        // 建物購入金額
        $price = number_format($realEstate['GetRealEstate'][1]['pay_sum']);
        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(158.3, 75.5);
        $pdf->MultiCell(34, 0, $price, 0, 'R');
        $pdf->SetXY(158.3, 101.3);
        $pdf->MultiCell(34, 0, $price, 0, 'R');
        // 建物の構造
        $base = $realEstate['GetRealEstate'][1]['house_base'];
        $pdf->SetFont($font, 'B', 10, true);
        $this->printCheckMark($pdf, $base, array(
            1 => array(51.5, 100.5),
            2 => array(62, 100.5),
            3 => array(84.6, 100.5),
            4 => array(84.6, 100.5),
            5 => array(105.9, 100.5),
            6 => array(105.9, 100.5),
            7 => array(105.9, 100.5),
            8 => array(119, 100.5),
        ));

        // 建物の償却費計算
        // 標準
        if ($realEstate['price_paper']) {
            $pdf->SetFont($font, 'B', 10, true);
            $this->printCheckMark($pdf, 1, array(1 => array(20.6, 123.5)));
        }

        $price1 = number_format($realEstate['GetRealEstate'][1]['pay_sum']);            // 購入価格
        $price2 = $realEstate['shoukyaku_rate'];                                        // 償却率
        $price3 = $realEstate['GetRealEstate'][1]['past_years']               ;         // 経過年数
        $price4 = number_format($realEstate['GetRealEstate'][1]['depreciation_cost']);  // 償却費相当額
        $price5 = number_format($realEstate['sum']['get_cost']);                        // 取得費
        $price6 = number_format($realEstate['sum']['get_share_cost']);                  // 共有の場合の取得費
        $price7 = number_format($realEstate['sum']['the_get_cost']);                    // 共有があった場合の取得費

        //概算所得費の場合は表示しない
        if(strpos($address,'%') === false){
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(19.3, 128.2);
          $pdf->MultiCell(28, 0, $price1, null, 'R');

          $pdf->SetFont($font, null, 11, true);
          $pdf->SetXY(65.5, 128);
          $pdf->MultiCell(16, 0, $price2, 0, 'C');
          $pdf->SetXY(85.1, 128);
          $pdf->MultiCell(16, 0, $price3, 0, 'C');

          $pdf->setFillColor(255);
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(105.4, 128.2);
          $pdf->Cell(26, 0, $price4, 0, 0, 'C', 1);
          $pdf->Cell(4, 0, '円', 0, 0, 'R', 1);
        }

        // 取得費
        if ($price6) {
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(158, 126.5);
            $pdf->Cell(33, 0, "({$price7}×持分)", 0, 2, 'R');
            $pdf->SetFont($font, null, 10, true);
            $pdf->Cell(33, 0, $price6, 0, 0, 'R');
        } else {
            $pdf->SetXY(158, 128);
            $pdf->SetFont($font, null, 12, true);
            $pdf->Cell(33, 0, $price5, 0, 0, 'R');
        }

        // 譲渡するために支払った費用
        $pdf->SetFont($font, null, 8, true);
        //// 仲介手数料 - 住所
        $address = h($realEstate['RealEstate']['broker_prefecture'] . $realEstate['RealEstate']['broker_city'] . $realEstate['RealEstate']['broker_address']);
        if ($address) {
            $address = $this->roundLineStrByWidthNot($address, 34);
            $height = (mb_strwidth($address, 'utf8') <= 34) ? 172.5 : 170.8;
            $pdf->SetXY(50.8, $height);
            $pdf->MultiCell(50, 0, $address, 0, 'L');
        }
        //// 仲介手数料 - 氏名
        $name = h($realEstate['RealEstate']['broker_name']);
        if ($name) {
            $name = $this->roundLineStrByWidthNot($name, 24);
            $height = (mb_strwidth($name, 'utf8') <= 24) ? 172.5 : 170.8;
            $pdf->SetXY(100.5, $height);
            $pdf->MultiCell(37, 0, $name, 0, 'L');
        }
        //// 仲介手数料 - 支払年月日
        $date = $realEstate['RealEstate']['pay_cost_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->SetXY(136.8, 172.5);
            $pdf->Cell(7, 0, $dates[0], 0, 0, 'C');
            $pdf->Cell(8, 0, ltrim($dates[1], 0), 0, null, 'C');
            $pdf->Cell(7, 0, ltrim($dates[2], 0), 0, null, 'C');
        }
        //// 仲介手数料 - 支払金額
        $pdf->SetFont($font, null, 10, true);
        $price = number_format($realEstate['RealEstate']['pay_cost_sum']);
        if ($price) {
            $pdf->SetXY(159, 172.2);
            $pdf->Cell(33, 0, $price, 0, 0, 'R');
        }

        // 収入印紙代
        $price = number_format($realEstate['RealEstate']['paper_sum']);
        if ($price) {
            $pdf->SetXY(159, 180.7);
            $pdf->Cell(33, 0, $price, 0, 0, 'R');
        }

        // その他１
        $pdf->SetFont($font, null, 10, true);
        //// タイトル
        $title = $realEstate['RealEstate']['cost_class1'];
        if ($title) {
            $pdf->SetXY(20, 189);
            $pdf->Cell(31, 0, $title, 0, 0, 'C');
        }
        //// 住所
        $pdf->SetFont($font, null, 8, true);
        $address = h($realEstate['RealEstate']['company_prefecture1'] . $realEstate['RealEstate']['company_city1'] . $realEstate['RealEstate']['company_address1']);
        if ($address) {
            $address = $this->roundLineStrByWidthNot($address, 34);
            $height = (mb_strwidth($address, 'utf8') <= 34) ? 189.4 : 187.8;
            $pdf->SetXY(50.8, $height);
            $pdf->MultiCell(50, 0, $address, 0, 'L');
        }
        //// 氏名
        $name = h($realEstate['RealEstate']['company_name1']);
        if ($name) {
            $name = $this->roundLineStrByWidthNot($name, 24);
            $height = (mb_strwidth($name, 'utf8') <= 24) ? 189.4 : 187.8;
            $pdf->SetXY(100.5, $height);
            $pdf->MultiCell(37, 0, $name, 0, 'L');
        }
        //// 支払年月日
        $date = $realEstate['RealEstate']['other_cost_pay_date1'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->SetXY(136.8, 189.4);
            $pdf->Cell(7, 0, $dates[0], 0, 0, 'C');
            $pdf->Cell(8, 0, ltrim($dates[1], 0), 0, null, 'C');
            $pdf->Cell(7, 0, ltrim($dates[2], 0), 0, null, 'C');
        }
        //// 支払金額
        $pdf->SetFont($font, null, 10, true);
        $price = number_format($realEstate['RealEstate']['other_cost_pay_sum1']);
        if ($price) {
            $pdf->SetXY(159, 189);
            $pdf->Cell(33, 0, $price, 0, 0, 'R');
        }

        // その他２
        $pdf->SetFont($font, null, 10, true);
        //// タイトル
        $title = $realEstate['RealEstate']['cost_class2'];
        if ($title) {
            $pdf->SetXY(20, 197.5);
            $pdf->Cell(31, 0, $title, 0, 0, 'C');
        }
        //// 住所
        $pdf->SetFont($font, null, 8, true);
        $address = h($realEstate['RealEstate']['company_prefecture2'] . $realEstate['RealEstate']['company_city2'] . $realEstate['RealEstate']['company_address2']);
        if ($address) {
            $address = $this->roundLineStrByWidthNot($address, 34);
            $height = (mb_strwidth($address, 'utf8') <= 34) ? 197.9 : 196.5;
            $pdf->SetXY(50.8, $height);
            $pdf->MultiCell(50, 0, $address, 0, 'L');
        }
        //// 氏名
        $name = h($realEstate['RealEstate']['company_name2']);
        if ($name) {
            $name = $this->roundLineStrByWidthNot($name, 24);
            $height = (mb_strwidth($name, 'utf8') <= 24) ? 197.9 : 196.5;
            $pdf->SetXY(100.5, $height);
            $pdf->MultiCell(37, 0, $name, 0, 'L');
        }
        //// 支払年月日
        $date = $realEstate['RealEstate']['other_cost_pay_date2'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->SetXY(136.8, 197.9);
            $pdf->Cell(7, 0, $dates[0], 0, 0, 'C');
            $pdf->Cell(8, 0, ltrim($dates[1], 0), 0, null, 'C');
            $pdf->Cell(7, 0, ltrim($dates[2], 0), 0, null, 'C');
        }
        //// 支払金額
        $pdf->SetFont($font, null, 10, true);
        $price = number_format($realEstate['RealEstate']['other_cost_pay_sum2']);
        if ($price) {
            $pdf->SetXY(159, 197.5);
            $pdf->Cell(33, 0, $price, 0, 0, 'R');
        }

        // 譲渡費用
        $price1 = number_format($realEstate['sum']['share_pay_cost']);
        $price2 = number_format($realEstate['sum']['pay_cost']);
        $price3 = number_format($realEstate['sum']['the_pay_cost']);
        if ($price1) {
            $pdf->SetFont($font, null, 8, true);
            $pdf->SetXY(158, 205);
            $pdf->Cell(33, 0, "({$price3}×持分)", 0, 2, 'R');
            $pdf->SetXY(158, 210);
            $pdf->SetFont($font, null, 12, true);
            $pdf->Cell(33, 0, $price1, 0, 0, 'R');
        } else {
            $pdf->SetXY(158, 210);
            $pdf->SetFont($font, null, 12, true);
            $pdf->Cell(33, 0, $price2, 0, 0, 'R');
        }

        // 土地
        if($realEstate['RealEstate']['land_cost_sum']){
          $price = number_format($realEstate['RealEstate']['land_cost_sum']);
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(100, 205);
          $pdf->Cell(10, 0, '土 地', 0, 0, 'L');
          $pdf->Cell(26, 0, $price, 0, 1, 'R');
        }
        if($realEstate['RealEstate']['house_cost_sum']){
          // 建物
          $price = number_format($realEstate['RealEstate']['house_cost_sum']);
          $pdf->SetXY(100, 209);
          $pdf->Cell(10, 0, '建 物', 0, 0, 'L');
          $pdf->Cell(26, 0, $price, 0, 1, 'R');
        }

        //買換え特例のときは表示しない
        $JyotoCheck = ClassRegistry::init('JyotoCheck');
        $flag = $JyotoCheck->findTotalFlag();
        if($flag['JyotoCheck']['check36total'] !=1){
          // 譲渡所得金額の計算
          if (!$realEstate['show_flag']['land_house'] && !$realEstate['show_flag']['share_total']) {
               $this->_putJyoto3CalculationPattern1($pdf, $font, $realEstate);
          } else if ($realEstate['show_flag']['land_house'] && !$realEstate['show_flag']['share_land_house']) {
              $this->_putJyoto3CalculationPattern2($pdf, $font, $realEstate);
          } else if (!$realEstate['show_flag']['land_house'] && $realEstate['show_flag']['share_total']) {
              $this->_putJyoto3CalculationPattern3($pdf, $font, $realEstate);
          } else {
              $this->_putJyoto3CalculationPattern4($pdf, $font, $realEstate);
          }
        }

        return $pdf;
    }

    /**
     * 譲渡の内訳書PDF出力 -　4面
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto4($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'h28_jyoto_uchiwakesho4.pdf');
        $ReakEstate = ClassRegistry::init('RealEstate');
        $realEstate = $ReakEstate->findForPage4();

        $pdf->SetLeftMargin(12.5);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(12.5, 53);


        // 所在地1
        $address = h($realEstate['NextRealEstate'][0]['land_prefecture'] . $realEstate['NextRealEstate'][0]['land_city'] . $realEstate['NextRealEstate'][0]['land_address']);
        $address = $this->roundLineStrByWidthNot($address, 39, 3);
        $pdf->MultiCell(64, 0, $address, 0, 'L', false, 0);

        $pdf->SetY($pdf->GetY() + 3.5, false);
        $pdf->Cell(15, 9, '宅地', 0, 0, 'C');
        $area = $realEstate['NextRealEstate'][0]['land_area'];
        $pdf->Cell(15.4, 9, $area, 0, 0, 'R');
        $pdf->Cell(15.4, 9, '居住用', 0, 0, 'C');
        // 契約日
        $date = $realEstate['NextRealEstate'][0]['land_contract_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.2, 9, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 9, $dates[1], 0, 0, 'R');
            $pdf->Cell(7.7, 9, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(22.9, 9, '', 0, 0, 'R');
        }
        // 取得日
        $date = $realEstate['NextRealEstate'][0]['land_get_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.2, 9, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 9, $dates[1], 0, 0, 'R');
            $pdf->Cell(7.8, 9, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23, 9, '', 0, 0, 'R');
        }
        // 使用開始日
        $date = $realEstate['NextRealEstate'][0]['living_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.7, 9, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 9, $dates[1], 0, 0, 'R');
            $pdf->Cell(7.8, 9, $dates[2], 0, 0, 'R');
        }

        $pdf->Ln();
        // 所在地2
        $address = h($realEstate['NextRealEstate'][0]['house_prefecture'] . $realEstate['NextRealEstate'][0]['house_city'] . $realEstate['NextRealEstate'][0]['house_address']);
        $address = $this->roundLineStrByWidthNot($address, 39, 3);
        $pdf->MultiCell(64, 0, $address, 0, 'L', false, 0);

        $pdf->SetY($pdf->GetY() + 3.5, false);
        $pdf->Cell(15, 9, '建物', 0, 0, 'C');
        $area = $realEstate['NextRealEstate'][0]['house_area'];
        $pdf->Cell(15.4, 9, $area, 0, 0, 'R');
        $pdf->Cell(15.4, 9, '居住用', 0, 0, 'C');
        // 契約日
        $date = $realEstate['NextRealEstate'][0]['house_contract_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.2, 9, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 9, $dates[1], 0, 0, 'R');
            $pdf->Cell(7.7, 9, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(22.9, 9, '', 0, 0, 'R');
        }
        // 取得日
        $date = $realEstate['NextRealEstate'][0]['house_get_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.2, 9, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 9, $dates[1], 0, 0, 'R');
            $pdf->Cell(7.8, 9, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23, 9, '', 0, 0, 'R');
        }
        // 使用開始日
        $date = $realEstate['NextRealEstate'][0]['living_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.7, 9, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 9, $dates[1], 0, 0, 'R');
            $pdf->Cell(7.8, 9, $dates[2], 0, 0, 'R');
        }

        // 費用の内訳
        $pdf->SetXY(39.7, 97.7);

        // 土地住所
        $address = h($realEstate['NextRealEstate'][0]['land_buy_prefecture'] . $realEstate['NextRealEstate'][0]['land_buy_city'] . $realEstate['NextRealEstate'][0]['land_buy_address']);
        $address = $this->roundLineStrByWidthNot($address, 55);
        $pdf->MultiCell(90, 8.5, $address, 0, 'L', false, 0);
        // 支払年月日
        $date = $realEstate['NextRealEstate'][0]['land_pay_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.3, 8.5, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 8.5, $dates[1], 0, 0, 'R');
            $pdf->Cell(8, 8.5, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23.3, 8.5, '', 0, 0, 'R');
        }
        // 支払金額
        $price = $realEstate['NextRealEstate'][0]['land_pay_sum'];
        if (!$price) {
          $price = '';
        }
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 0, 'R');


        $pdf->Ln();
        // タイトル
        $title = $realEstate['NextRealEstate'][0]['cost1_name'];
        $pdf->Cell(27.2, 8.5, $title, 0, 0, 'L');
        // 住所
        $address = h($realEstate['NextRealEstate'][0]['cost1_address']);
        $address = $this->roundLineStrByWidthNot($address, 55);
        $pdf->MultiCell(90, 8.5, $address, 0, 'L', false, 0);
        // 支払年月日
        $date = $realEstate['NextRealEstate'][0]['cost1_pay_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.3, 8.5, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 8.5, $dates[1], 0, 0, 'R');
            $pdf->Cell(8, 8.5, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23.3, 8.5, '', 0, 0, 'R');
        }
        // 支払金額
        $price = $realEstate['NextRealEstate'][0]['cost1_pay_sum'];
        if (!$price) {
          $price = '';
        }
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 0, 'R');

        $pdf->Ln();
        // タイトル
        $title = $realEstate['NextRealEstate'][0]['cost2_name'];
        $pdf->Cell(27.2, 8.5, $title, 0, 0, 'L');
        // 住所
        $address = h($realEstate['NextRealEstate'][0]['cost2_address']);
        $address = $this->roundLineStrByWidthNot($address, 55);
        $pdf->MultiCell(90, 8.5, $address, 0, 'L', false, 0);
        // 支払年月日
        $date = $realEstate['NextRealEstate'][0]['cost2_pay_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.3, 8.5, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 8.5, $dates[1], 0, 0, 'R');
            $pdf->Cell(8, 8.5, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23.3, 8.5, '', 0, 0, 'R');
        }
        // 支払金額
        $price = $realEstate['NextRealEstate'][0]['cost2_pay_sum'];
        if (!$price) {
          $price = '';
        }
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 0, 'R');

        $pdf->Ln();
        $pdf->SetX(39.7, false);
        // 建物住所
        $address = h($realEstate['NextRealEstate'][0]['house_buy_prefecture'] . $realEstate['NextRealEstate'][0]['house_buy_city'] . $realEstate['NextRealEstate'][0]['house_buy_address']);
        $address = $this->roundLineStrByWidthNot($address, 55);
        $pdf->MultiCell(90, 8.5, $address, 0, 'L', false, 0);
        // 支払年月日
        $date = $realEstate['NextRealEstate'][0]['house_pay_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.3, 8.5, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 8.5, $dates[1], 0, 0, 'R');
            $pdf->Cell(8, 8.5, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23.3, 8.5, '', 0, 0, 'R');
        }
        // 支払金額
        $price = $realEstate['NextRealEstate'][0]['house_pay_sum'];
        if (!$price) {
          $price = '';
        }
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 0, 'R');

        $pdf->Ln();
        // タイトル
        $title = $realEstate['NextRealEstate'][0]['cost3_name'];
        $pdf->Cell(27.2, 8.5, $title, 0, 0, 'L');
        // 住所
        $address = h($realEstate['NextRealEstate'][0]['cost3_address']);
        $address = $this->roundLineStrByWidthNot($address, 55);
        $pdf->MultiCell(90, 8.5, $address, 0, 'L', false, 0);
        // 支払年月日
        $date = $realEstate['NextRealEstate'][0]['cost3_pay_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.3, 8.5, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 8.5, $dates[1], 0, 0, 'R');
            $pdf->Cell(8, 8.5, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23.3, 8.5, '', 0, 0, 'R');
        }
        // 支払金額
        $price = $realEstate['NextRealEstate'][0]['cost3_pay_sum'];
        if (!$price) {
          $price = '';
        }
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 0, 'R');

        $pdf->Ln();
        // タイトル
        $title = $realEstate['NextRealEstate'][0]['cost4_name'];
        $pdf->Cell(27.2, 8.5, $title, 0, 0, 'L');
        // 住所
        $address = h($realEstate['NextRealEstate'][0]['cost4_address']);
        $address = $this->roundLineStrByWidthNot($address, 55);
        $pdf->MultiCell(90, 8.5, $address, 0, 'L', false, 0);
        // 支払年月日
        $date = $realEstate['NextRealEstate'][0]['cost4_pay_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(8.3, 8.5, $dates[0], 0, 0, 'R');
            $pdf->Cell(7, 8.5, $dates[1], 0, 0, 'R');
            $pdf->Cell(8, 8.5, $dates[2], 0, 0, 'R');
        } else {
            $pdf->Cell(23.3, 8.5, '', 0, 0, 'R');
        }
        // 支払金額
        $price = $realEstate['NextRealEstate'][0]['cost4_pay_sum'];
        if (!$price) {
          $price = '';
        }
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 2, 'R');

        //未払金
        if($realEstate['NextRealEstate'][0]['maybe_pay_sum']){
          $pdf->SetFont($font, null, 7, true);
          $pdf->SetXY(157, 146.5);
          $price = number_format($realEstate['NextRealEstate'][0]['maybe_pay_sum']);
          $price = '（未払金 '.$price.'円）';
          $pdf->Cell(30.5, 8.5,$price , 0, 0, 'R');
        }
        // 取得価格の合計
        $price = $realEstate['sum']['new_total_cost'];
        if ($price) {
          if($realEstate['NextRealEstate'][0]['maybe_pay_sum']){
            $pdf->SetFont($font, null, 9, true);
            $pdf->SetXY(150, 150);
            $pdf->Cell(35.5, 8.5, number_format($price), 0, 0, 'R');
          } else {
            $pdf->Cell(33.5, 8.5, number_format($price), 0, 0, 'R');
          }
        }

        // 譲渡所得金額の計算
        switch ($realEstate['sum']['class']) {
            case '長期' :
                $pdf->RoundedRect(16, 226.8, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(16, 220.8, 10, 3.7, 1.5);
                break;
        }
        // 特例適用条文
        if ($realEstate['sum']['special_law_class'] == '措') {
            $pdf->Circle(41.2, 222.9, 2);
        }
        $realEstate['sum']['special_law_text_2'] = '2の5';
        $pdf->SetXY(36, 226.5);
        $pdf->Cell(4, 0, $realEstate['sum']['special_law_text'], 0, 0, 'R');
        $pdf->SetX(42.5);
        $pdf->Cell(8.2, 0, $realEstate['sum']['special_law_text_2'], 0, 0, 'R');

        $pdf->SetXY(53, 220);
        $pdf->setCellPaddings('','',5,'');
        // 収入金額
        $price = number_format($realEstate['sum']['real_receive_sum']);
        $pdf->Cell(49.2, 12, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['sum']['real_total_cost']);
        $pdf->Cell(49.2, 12, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['sum']['sum_real_shotoku']);
        $pdf->Cell(40.5, 12, $price, 0, 0, 'R');

        $pdf->setCellPaddings('','','','');

        return $pdf;

    }

    /**
     * 居住用財産の譲渡損失の金額の明細書PDF出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto_kyoju($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'loss_detail_415.pdf');
        $ReakEstate = ClassRegistry::init('RealEstate');
        $realEstate = $ReakEstate->findFor41_5();

        $pdf->SetFillColor(200);
        $pdf->SetLeftMargin(12.5);

        // 年
        $pdf->SetFont($font, null, 13, true);
        $pdf->SetXY(31, 7);
        $pdf->Cell(8, 0, $realEstate['RealEstate']['for_japanese_year'], 0, 2, 'C');

        // 住所
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(36, 34.5);
        $address = h($realEstate['NameList']['prefecture']. $realEstate['NameList']['city']. $realEstate['NameList']['address']);
        $address = $this->roundLineStrByWidthNot($address, 35, 4);
        $pdf->MultiCell(59, 17, $address, 0, 'L', false, 0);

        // 氏名
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->setCellPaddings("", "", "", 0.5);
        $name = h($realEstate['NameList']['name_furigana']);
        $name = $this->roundLineStrByWidthNot($name, 25, 1);
        $pdf->SetX(111.3);
        $pdf->Cell(35.3, 6, $name, 0, 2, 'L', 0, "", 0, false, 'T', 'B');

        $pdf->SetFont($font, null, 11, true);
        $pdf->setCellPaddings("", 0.5, "", 0);
        $name = h($realEstate['NameList']['name']);
        $name = $this->roundLineStrByWidthNot($name, 17);
        $pdf->MultiCell(35.3, 10.8, $name, 0, 'L', false, 0, "", "", true, 0, false, true, 10.8, 'M');

        // 電話番号
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetFont($font, null, 11, true);
        if ($realEstate['NameList']['phone_number']) {
            $numbers = explode('-', $realEstate['NameList']['phone_number']);
            $pdf->SetXY(162, 35.7);
            $pdf->Cell(11, 0, $numbers[0], 0, 2, 'C');
            $pdf->SetXY(159, 42);
            $pdf->Cell(36, 0, $numbers[1] . '-' . $numbers[2], 0, 2, 'C');
        }

        ///// 譲渡した資産に関する明細
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->setCellPaddings(0.8, 0, 0.8, 0);
        $pdf->SetLeftMargin(114);
        $pdf->SetXY(114, 86.2);
        // 建物所在地
        $address = h($realEstate['RealEstate']['sale_old_prefecture'] . $realEstate['RealEstate']['sale_old_city'] . $realEstate['RealEstate']['sale_old_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 3);
        $pdf->MultiCell(40.5, 11.7, $address, 0, 'L', false, 0);
        // 土地所在地
        $pdf->MultiCell(40.5, 11.7, '同左', 0, 'L', false, 0);

        $pdf->Ln();

        // 建物面積
        $pdf->Cell(20.3, 7, '自己の居住用', 0, 0, 'C');
        $pdf->Cell(15, 7, $realEstate['RealEstate']['house_area'], 0, 0, 'R');
        // 土地面積
        if($realEstate['RealEstate']['land_area'] && $realEstate['RealEstate']['land_area_paper']){
          if($realEstate['RealEstate']['land_area'] > $realEstate['RealEstate']['land_area_paper']){
            $land_area = $realEstate['RealEstate']['land_area'] ;
          } else {
            $land_area = $realEstate['RealEstate']['land_area_paper'] ;
          }
        } elseif($realEstate['RealEstate']['land_area']){
          $land_area = $realEstate['RealEstate']['land_area'] ;
        } elseif($realEstate['RealEstate']['land_area_paper']){
          $land_area = $realEstate['RealEstate']['land_area_paper'] ;
        }
        $pdf->SetX($pdf->GetX() + 5.5);
        $pdf->Cell(20.3, 7, '自己の居住用', 0, 0, 'C');
        $pdf->Cell(15, 7, $realEstate['RealEstate']['land_area'], 0, 0, 'R');

        $pdf->Ln();

        // 居住期間
        $pdf->SetFont($font, null, 9.5, true);
        $date = $realEstate['RealEstate']['living_start_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(22, 6.7, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.7, $dates[1], 0, 0, 'R');
        } else {
            $pdf->SetX($pdf->GetX() + 33);
        }

        $date = $realEstate['RealEstate']['living_end_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(20.5, 6.7, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.7, $dates[1], 0, 0, 'R');
        }

        $pdf->Ln();

        // 譲渡先住所 - 建物
        $address = h($realEstate['RealEstate']['buyer_prefecture'] . $realEstate['RealEstate']['buyer_city'] . $realEstate['RealEstate']['buyer_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 2);
        $pdf->MultiCell(40.5, 9.5, $address, 0, 'L', false, 0);
        // 譲渡先住所 - 土地
        $pdf->MultiCell(40.5, 9.5, '同左', 0, 'L', false, 0);

        $pdf->Ln();

        // 譲渡先氏名 - 建物
        $pdf->SetFont($font, null, 8, true);
        $name = h($realEstate['RealEstate']['buyer_name']);
        $name = $this->roundLineStrByWidthNot($name, 28, 1);
        $pdf->Cell(40.5, 7.5, $name, 0, 0, 'C');
        // 譲渡先氏名 - 土地
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->MultiCell(40.5, 7.5, '同左', 0, 'L', false, 0);

        $pdf->Ln();

        // 譲渡契約締結日
        $date = $realEstate['RealEstate']['sale_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');

            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        }
        $pdf->Ln();
        // 譲渡した年月日
        $date = $realEstate['RealEstate']['real_sale_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');

            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        }
        $pdf->Ln();
        // 資産を取得した時期
        $date = $realEstate['GetRealEstate'][0]['get_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        } else {
            $pdf->SetX($pdf->GetX() + 35.5);
        }
        $date = $realEstate['GetRealEstate'][0]['get_date'];
        if ($date) {
            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        }

        $pdf->SetLeftMargin(73.7);
        $pdf->Ln();

        // 譲渡価額
        $price = number_format($realEstate['sum']['receive_sum']);
        $pdf->Cell(35, 6.6, $price, 0, 0, 'R');

        if($realEstate['sum']['receive_sum'] == ($realEstate['house']['receive_sum'] + $realEstate['land']['receive_sum'])){
          $price = number_format($realEstate['house']['receive_sum']);
        } else {
          $price = '';
        }
          $pdf->SetX($pdf->GetX() + 5.3);
          $pdf->Cell(35, 6.6, $price, 0, 0, 'R');
        if($realEstate['sum']['receive_sum'] == ($realEstate['house']['receive_sum'] + $realEstate['land']['receive_sum'])){
          $price = number_format($realEstate['land']['receive_sum']);
        } else {
          $price = '';
        }

          $pdf->SetX($pdf->GetX() + 5.6);
          $pdf->Cell(35, 6.6, $price, 0, 0, 'R');

          $pdf->Ln();


        // 取得価額
        //建物の持分
        $house_share_up = 1;
        $house_share_down = 1;
        if($realEstate['RealEstate']['your_share_up']){
          $house_share_up = $realEstate['RealEstate']['your_share_up'];
        }
        if($realEstate['RealEstate']['your_share_down']){
          $house_share_down = $realEstate['RealEstate']['your_share_down'];
        }

        //減価償却費（持分考慮）
        $depreciation_cost = $realEstate['GetRealEstate'][1]['depreciation_cost'] * $house_share_up / $house_share_down;

        $price1 = $realEstate['sum']['get_cost'] + $depreciation_cost;
        $price11 = number_format($price1);
        $pdf->Cell(35, 6.6, $price11, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.3);
        $price2 = $realEstate['house']['get_cost'] * $house_share_up / $house_share_down + $depreciation_cost;
        $price22 = number_format($price2);
        $pdf->Cell(35, 6.6, $price22, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.6);
        $price3 = number_format($price1-$price2);
        $pdf->Cell(35, 6.6, $price3, 0, 0, 'R');

        $pdf->Ln();

        // 償却費相当額
        if($realEstate['GetRealEstate'][1]['depreciation_cost']){
          $price = number_format($depreciation_cost );
        } else {
          $price = '';
        }
          $pdf->Cell(35, 6.75, $price, 0, 0, 'R');

          $pdf->SetX($pdf->GetX() + 5.3);
          $pdf->Cell(35, 6.75, $price, 0, 0, 'R');

          $pdf->Ln();

        // 差引
        $price1 = number_format($realEstate['sum']['get_cost']);
        $pdf->Cell(35, 6.75, $price1, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.3);
        $price2 = number_format($realEstate['house']['get_cost']);
        $pdf->Cell(35, 6.75, $price2, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.6);
        $price3 = number_format($realEstate['sum']['get_cost'] - ($realEstate['house']['get_cost'] * $house_share_up / $house_share_down));
        $pdf->Cell(35, 6.75, $price3, 0, 0, 'R');

        $pdf->Ln();

        // 譲渡に要した費用
        $price = number_format($realEstate['sum']['pay_cost']);
        $pdf->Cell(35, 6.75, $price, 0, 0, 'R');

        if($realEstate['sum']['pay_cost'] == ($realEstate['house']['pay_cost'] + $realEstate['land']['pay_cost'])){
          $price = number_format($realEstate['house']['pay_cost']);
        } else {
          $price ='';
        }
        $pdf->SetX($pdf->GetX() + 5.3);
        $pdf->Cell(35, 6.75, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.6);
        if($realEstate['sum']['pay_cost'] == ($realEstate['house']['pay_cost'] + $realEstate['land']['pay_cost'])){
          $price = number_format($realEstate['land']['pay_cost']);
        } else {
          $price ='';
        }
        $pdf->Cell(35, 6.75, $price, 0, 0, 'R');

        $pdf->Ln();

        // 居住用財産の譲渡損失の金額
        $price = number_format($realEstate['sum']['shotoku']);
        $price = str_replace('-', '△', $price);
        $pdf->Cell(35, 8.5, $price, 0, 0, 'R');

        if($realEstate['sum']['shotoku'] == ($realEstate['house']['shotoku'] + $realEstate['land']['shotoku'])){
          $price = number_format($realEstate['house']['shotoku']);
          $price = str_replace('-', '△', $price);
        } else {
          $price ='';
        }
          $pdf->SetX($pdf->GetX() + 5.3);

          $pdf->Cell(35, 8.5, $price, 0, 0, 'R');

          if($realEstate['sum']['shotoku'] == ($realEstate['house']['shotoku'] + $realEstate['land']['shotoku'])){
            $price = number_format($realEstate['land']['shotoku']);
            $price = str_replace('-', '△', $price);
          } else {
            $price ='';
          }

          $pdf->SetX($pdf->GetX() + 5.6);
          $pdf->Cell(35, 8.5, $price, 0, 0, 'R');

        ////////// 買い換えた資産に関する明細
        $pdf->SetLeftMargin(114);
        $pdf->SetXY(114, 208.5);

        // 建物所在地
        $address = h($realEstate['NextRealEstate'][0]['house_prefecture'] . $realEstate['NextRealEstate'][0]['house_city'] . $realEstate['NextRealEstate'][0]['house_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 3);
        $pdf->MultiCell(40.5, 11.7, $address, 0, 'L', false, 0);
        // 土地所在地
        $address = h($realEstate['NextRealEstate'][0]['land_prefecture'] . $realEstate['NextRealEstate'][0]['land_city'] . $realEstate['NextRealEstate'][0]['land_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 3);
        $pdf->MultiCell(40.5, 11.7, $address, 0, 'L', false, 0);

        $pdf->Ln();

        // 建物面積
        $pdf->Cell(20.6, 7, '自己の居住用', 0, 0, 'C');
        $pdf->Cell(15, 7, $realEstate['NextRealEstate'][0]['house_area'], 0, 0, 'R');
        // 土地面積
        $pdf->SetX($pdf->GetX() + 5.2);
        $pdf->Cell(20.3, 7, '自己の居住用', 0, 0, 'C');
        $pdf->Cell(15, 7, $realEstate['NextRealEstate'][0]['land_area'], 0, 0, 'R');

        $pdf->Ln();

        // 買換え資産の取得日
        $date = $realEstate['NextRealEstate'][0]['house_get_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        } else {
            $pdf->SetX($pdf->GetX() + 35.5);
        }
        $date = $realEstate['NextRealEstate'][0]['land_get_date'];
        if ($date) {
            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(14.5, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        }

        $pdf->Ln();

        $date = $realEstate['NextRealEstate'][0]['living_date'];
        if ($date) {
            $pdf->Cell(32, 6.8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 6.8, $dates[1], 0, 0, 'R');
            $pdf->Cell(10, 6.8, $dates[2], 0, 0, 'R');
        }

        $pdf->Ln();
        $pdf->SetX(73.7);

        // 買取資産の取得価額
        $price = number_format($realEstate['NextRealEstate'][0]['house_land_price']);
        $pdf->Cell(35, 6.6, $price, 0, 0, 'R');

        if($realEstate['NextRealEstate'][0]['house_land_price'] == ($realEstate['NextRealEstate'][0]['house_price'] + $realEstate['NextRealEstate'][0]['land_price'])){
            $price = number_format($realEstate['NextRealEstate'][0]['house_price']);
        } else {
          $price = '';
        }
        $pdf->SetX($pdf->GetX() + 5.3);
        $pdf->Cell(35, 6.6, $price, 0, 0, 'R');

        if($realEstate['NextRealEstate'][0]['house_land_price'] == ($realEstate['NextRealEstate'][0]['house_price'] + $realEstate['NextRealEstate'][0]['land_price'])){
            $price = number_format($realEstate['NextRealEstate'][0]['land_price']);
        } else {
          $price = '';
        }
        $pdf->SetX($pdf->GetX() + 5.6);
        $pdf->Cell(35, 6.6, $price, 0, 0, 'R');

        $pdf->Ln();

        // 買入先所在地 - 建物
        $address = h($realEstate['NextRealEstate'][0]['house_buy_prefecture'] . $realEstate['NextRealEstate'][0]['house_buy_city'] . $realEstate['NextRealEstate'][0]['house_buy_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 2);
        $pdf->MultiCell(40.5, 9.8, $address, 0, 'L', false, 0);

        // 買入先所在地 - 土地
        $address = h($realEstate['NextRealEstate'][0]['land_buy_prefecture'] . $realEstate['NextRealEstate'][0]['land_buy_city'] . $realEstate['NextRealEstate'][0]['land_buy_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 2);
        $pdf->MultiCell(40.5, 9.8, $address, 0, 'L', false, 0);

        $pdf->Ln();

        // 氏名 - 建物
        $name = h($realEstate['NextRealEstate'][0]['house_buy_company']);
        $name = $this->roundLineStrByWidthNot($name, 23, 1);
        $pdf->Cell(40.7, 6.6, $name, 0, 0, 'C');

        // 氏名 - 土地
        $name = h($realEstate['NextRealEstate'][0]['land_buy_company']);
        $name = $this->roundLineStrByWidthNot($name, 23, 1);
        $pdf->Cell(40.7, 6.6, $name, 0, 0, 'C');

        $pdf->Ln();

        // 借入先
        $pdf->SetX($pdf->GetX() + 17);
        $pdf->SetFont($font, null, 8, true);
        $name = h($realEstate['JyotoCheck'][0]['loan_name']);
        $name = $this->roundLineStrByWidthNot($name, 42, 1);
        $pdf->Cell(60, 5, $name, 0, 0, 'C');
        $pdf->Ln();
        // 金額
        $pdf->SetFont($font, null, 9.5, true);
        $price = number_format($realEstate['RealEstate']['next_loan_sum']);
        $pdf->Cell(76, 5, $price, 0, 0, 'R');

        // 税理士
        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY(20, 279);
        $name = $realEstate['User']['tax_accountant_name'];
        $pdf->Cell(86, 5, $name, 0, 0, 'L');


        $pdf->SetXY(75, 283.8);
        $pdf->SetFont($font, null, 8, true);
        $phone = $realEstate['User']['tax_accountant_phone'];
        $pdf->Cell(35, 3, $phone, 0, 0, 'C');


        return $pdf;
    }

    /**
     * 特定居住用財産の譲渡損失の金額の明細書PDF出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto_tokutei_kyoju($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'loss_detail_4152.pdf');
        $ReakEstate = ClassRegistry::init('RealEstate');
        $realEstate = $ReakEstate->findFor41_5_2();

        $pdf->SetFillColor(200);
        $pdf->SetLeftMargin(12.5);

        // 年
        $pdf->SetFont($font, null, 13, true);
        $pdf->SetXY(32.5, 10);
        $pdf->Cell(8, 0, $realEstate['RealEstate']['for_japanese_year'], 0, 2, 'C');

        // 住所
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(41, 46.5);
        $address = h($realEstate['NameList']['prefecture']. $realEstate['NameList']['city']. $realEstate['NameList']['address']);
        $address = $this->roundLineStrByWidthNot($address, 31, 4);
        $pdf->MultiCell(55.5, 21, $address, 0, 'L', false, 0);

        // 氏名
        $pdf->SetFont($font, null, 7.5, true);
        $name = h($realEstate['NameList']['name_furigana']);
        $name = $this->roundLineStrByWidthNot($name, 25, 1);
        $pdf->SetX($pdf->GetX() + 16.5);
        $pdf->Cell(35.3, 4, $name, 0, 2, 'L');

        $pdf->SetY($pdf->GetY() + 3.5, false);
        $pdf->SetFont($font, null, 10, true);
        $name = h($realEstate['NameList']['name']);
        $name = $this->roundLineStrByWidthNot($name, 19);
        $pdf->MultiCell(35.3, 10.8, $name, 0, 'L', false, 0);

        // 電話番号
        $pdf->SetFont($font, null, 11, true);
        if ($realEstate['NameList']['phone_number']) {
            $numbers = explode('-', $realEstate['NameList']['phone_number']);
            $pdf->SetXY($pdf->GetX() + 15.5, 47);
            $pdf->Cell(11, 0, $numbers[0], 0, 2, 'C');
            $pdf->SetXY(160.5, 57.5);
            $pdf->Cell(36, 0, $numbers[1] . '-' . $numbers[2], 0, 2, 'C');
        }

        ///// 譲渡した資産に関する明細
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->setCellPaddings(0.8, 0, 0.8, 0);
        $pdf->SetLeftMargin(115.8);
        $pdf->SetXY(115.8, 104.5);

        // 建物所在地
        $address = h($realEstate['RealEstate']['sale_old_prefecture'] . $realEstate['RealEstate']['sale_old_city'] . $realEstate['RealEstate']['sale_old_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 3);
        $pdf->MultiCell(40.5, 13.7, $address, 0, 'L', false, 0);
        // 土地所在地
        $pdf->MultiCell(40.5, 13.7, '同左', 0, 'L', false, 0);

        $pdf->Ln();

        // 建物面積
        $pdf->Cell(20.1, 8.5, '自己の居住用', 0, 0, 'C');
        $pdf->Cell(15, 8.5, $realEstate['RealEstate']['house_area'], 0, 0, 'R');
        // 土地面積
        $pdf->SetX($pdf->GetX() + 5.5);
        $pdf->Cell(20.1, 8.5, '自己の居住用', 0, 0, 'C');
        $pdf->Cell(15, 8.5, $realEstate['RealEstate']['land_area'], 0, 0, 'R');

        $pdf->Ln();

        // 居住期間
        $pdf->SetFont($font, null, 9.5, true);
        $date = $realEstate['RealEstate']['living_start_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(20, 8.7, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8.7, $dates[1], 0, 0, 'R');
        } else {
            $pdf->SetX($pdf->GetX() + 31);
        }

        $date = $realEstate['RealEstate']['living_end_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(22.5, 8.7, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8.7, $dates[1], 0, 0, 'R');
        }

        $pdf->Ln();

        // 譲渡先住所 - 建物
        $address = h($realEstate['RealEstate']['buyer_prefecture'] . $realEstate['RealEstate']['buyer_city'] . $realEstate['RealEstate']['buyer_address']);
        $address = $this->roundLineStrByWidthNot($address, 23, 3);
        $pdf->MultiCell(40.5, 13.3, $address, 0, 'L', false, 0);
        // 譲渡先住所 - 土地
        $pdf->MultiCell(40.5, 13.3, '同左', 0, 'L', false, 0);

        $pdf->Ln();

        // 譲渡先氏名 - 建物
        $pdf->SetFont($font, null, 9, true);
        $name = h($realEstate['RealEstate']['buyer_name']);
        $name = $this->roundLineStrByWidthNot($name, 28);
        $pdf->MultiCell(40.5, 8.5, $name, 0, 'C', false, 0, "", "", true, 0, false, true, 8.5, 'M');
        // 譲渡先氏名 - 土地
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->MultiCell(40.5, 8.5, '同左', 0, 'L', false, 0);

        $pdf->Ln();

        // 譲渡契約締結日
        $date = $realEstate['RealEstate']['sale_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[2], 0, 0, 'R');

            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(13.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[2], 0, 0, 'R');
        }
        $pdf->Ln();

        // 譲渡契約締結日の前日における....
        $pdf->SetY($pdf->GetY() + 1, false);
        $name = $realEstate['JyotoCheck'][0]['loan_name'];
        $pdf->Cell(80, 14, $name, 0, 0, 'C');
        $pdf->Ln();

        //借入金額
        $price = number_format($realEstate['RealEstate']['loan_sum']);
        $pdf->Cell(76, 5.4, $price, 0, 0, 'R');
        $pdf->Ln();


        // 譲渡した年月日
        $date = $realEstate['RealEstate']['real_sale_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[2], 0, 0, 'R');

            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(13.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[2], 0, 0, 'R');
        }
        $pdf->Ln();
        // 資産を取得した時期
        $date = $realEstate['GetRealEstate'][1]['get_date'];
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[2], 0, 0, 'R');
        } else {
            $pdf->SetX($pdf->GetX() + 36.5);
        }
        $date = $realEstate['GetRealEstate'][0]['get_date'];
        if ($date) {
            $pdf->SetX($pdf->GetX() + 4.8);
            $pdf->Cell(13.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(11, 8, $dates[2], 0, 0, 'R');
        }

        $pdf->SetLeftMargin(77.5);
        $pdf->Ln();

        // 譲渡価額
        $price = number_format($realEstate['sum']['receive_sum']);
        $pdf->Cell(33, 7.85, $price, 0, 0, 'R');

          $pdf->SetX($pdf->GetX() + 5.4);
        if($realEstate['sum']['receive_sum'] == ($realEstate['house']['receive_sum'] + $realEstate['land']['receive_sum'])){
          $price = number_format($realEstate['house']['receive_sum']);
        } else {
          $price = '';
        }
          $pdf->Cell(35, 7.85, $price, 0, 0, 'R');

          $pdf->SetX($pdf->GetX() + 5.4);
        if($realEstate['sum']['receive_sum'] == ($realEstate['house']['receive_sum'] + $realEstate['land']['receive_sum'])){
          $price = number_format($realEstate['land']['receive_sum']);
        }
          $pdf->Cell(35, 7.85, $price, 0, 0, 'R');

        $pdf->Ln();

        // 取得価額
        //建物の持分
        $house_share_up = 1;
        $house_share_down = 1;
        if($realEstate['RealEstate']['your_share_up']){
          $house_share_up = $realEstate['RealEstate']['your_share_up'];
        }
        if($realEstate['RealEstate']['your_share_down']){
          $house_share_down = $realEstate['RealEstate']['your_share_down'];
        }

        //減価償却費（持分考慮）
        $depreciation_cost = $realEstate['GetRealEstate'][1]['depreciation_cost'] * $house_share_up / $house_share_down;

        $price1 = $realEstate['sum']['get_cost'] + $depreciation_cost;
        $price11 = number_format($price1);
        $pdf->Cell(33, 8, $price11, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        $price2 = $realEstate['house']['get_cost'] * $house_share_up / $house_share_down + $depreciation_cost;
        $price22 = number_format($price2);
        $pdf->Cell(35, 8, $price22, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        $price3 = number_format($price1 - $price2);
        $pdf->Cell(35, 8, $price3, 0, 0, 'R');

        $pdf->Ln();

        // 償却費相当額
        $price = number_format($depreciation_cost);
        $pdf->Cell(33, 8, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        $price = number_format($depreciation_cost);
        $pdf->Cell(35, 8, $price, 0, 0, 'R');

        $pdf->Ln();

        // 差引
        $price = number_format($realEstate['sum']['get_cost']);
        $pdf->Cell(33, 7.85, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        $price = number_format($realEstate['house']['get_cost'] * $house_share_up / $house_share_down );
        $pdf->Cell(35, 7.85, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        $price = number_format($realEstate['sum']['get_cost'] - ($realEstate['house']['get_cost'] * $house_share_up / $house_share_down) );
        $pdf->Cell(35, 7.85, $price, 0, 0, 'R');

        $pdf->Ln();

        // 譲渡に要した費用
        $price = number_format($realEstate['sum']['pay_cost']);
        $pdf->Cell(33, 8, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        if($realEstate['sum']['pay_cost'] == ($realEstate['house']['pay_cost'] + $realEstate['land']['pay_cost'])){
          $price = number_format($realEstate['house']['pay_cost']);
        } else {
          $price = '';
        }
        $pdf->Cell(35, 8, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        if($realEstate['sum']['pay_cost'] == ($realEstate['house']['pay_cost'] + $realEstate['land']['pay_cost'])){
          $price = number_format($realEstate['land']['pay_cost']);
        } else {
          $price = '';
        }
        $pdf->Cell(35, 8, $price, 0, 0, 'R');

        $pdf->Ln();

        // 特定居住用財産の譲渡損失の金額
        $price = number_format($realEstate['sum']['shotoku']);
        $price = str_replace('-', '△', $price);
        $pdf->Cell(33, 15, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        if($realEstate['sum']['shotoku'] == ($realEstate['house']['shotoku'] + $realEstate['land']['shotoku'])){
        $price = number_format($realEstate['house']['shotoku']);
        $price = str_replace('-', '△', $price);
        } else {
          $price = '';
        }
        $pdf->Cell(35, 15, $price, 0, 0, 'R');

        $pdf->SetX($pdf->GetX() + 5.4);
        if($realEstate['sum']['shotoku'] == ($realEstate['house']['shotoku'] + $realEstate['land']['shotoku'])){
          $price = number_format($realEstate['land']['shotoku']);
          $price = str_replace('-', '△', $price);
        } else {
          $price = '';
        }
        $pdf->Cell(35, 15, $price, 0, 0, 'R');

        // 税理士
        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY(25, 272);
        $name = $realEstate['User']['tax_accountant_name'];
        $pdf->Cell(86, 5, $name, 0, 0, 'L');


        $pdf->SetXY(75, 277);
        $pdf->SetFont($font, null, 8, true);
        $phone = $realEstate['User']['tax_accountant_phone'];
        $pdf->Cell(35, 3, $phone, 0, 0, 'C');

        return $pdf;
    }

    /**
     * 譲渡の内訳書PDF出力 -　3面 - 譲渡所得金額の計算表出力 - パターン１
     * ($realEstate['show_flag']['land_house'] == false AND $realEstate['show_flag']['share_total'] == false)
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $realEstate
     */
    private function _putJyoto3CalculationPattern1(&$pdf, $font, $realEstate) {
        switch ($realEstate['sum']['class']) {
            case '長期' :
                $pdf->RoundedRect(20.5, 242, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(20.5, 236.5, 10, 3.7, 1.5);
                break;
        }

        if ($realEstate['sum']['special_law_class'] == '措') {
            $pdf->Circle(42, 238, 2);
        }

        $pdf->SetFont($font, null, 9, true);
        // 特例適用条文
        if ($realEstate['sum']['special_law_class_double']) {
            if ($realEstate['sum']['special_law_text'] < $realEstate['sum']['special_law_text_double']) {
                $val1_top    = $realEstate['sum']['special_law_text'];
                $val1_bottom = $realEstate['sum']['special_law_text_double'];
                $val2_top    = $realEstate['sum']['special_law_text_2'];
                $val2_bottom = $realEstate['sum']['special_law_text_2_double'];
            } else {
                $val1_top    = $realEstate['sum']['special_law_text_double'];
                $val1_bottom = $realEstate['sum']['special_law_text'];
                $val2_top    = $realEstate['sum']['special_law_text_2_double'];
                $val2_bottom = $realEstate['sum']['special_law_text_2'];
            }
            $pdf->SetXY(33, 238.7);
            $pdf->Cell(8, 0, $val1_top, 0, 2, 'R');
            $pdf->SetXY(33, 241.5);
            $pdf->Cell(8, 0, $val1_bottom, 0, 0, 'R');
            $pdf->SetXY(43.7, 238.8);
            $pdf->Cell(8, 0, $val2_top, 0, 2, 'R');
            $pdf->SetXY(43.7, 241.5);
            $pdf->Cell(8, 0, $val2_bottom, 0, 2, 'R');
        } else {
            $pdf->SetXY(37, 241.4);
            $pdf->Cell(4, 0, $realEstate['sum']['special_law_text'], 0, 1, 'R');
            $pdf->SetXY(42.8, 241.4);
            $pdf->Cell(9, 0, $realEstate['sum']['special_law_text_2'], 0, 1, 'R');
        }
        // 収入金額
        $price = number_format($realEstate['sum']['receive_sum']);
        $pdf->SetXY(53.5, 241.4);
        $pdf->Cell(28.5, 0, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['sum']['total_cost']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 差引金額
        $price = number_format($realEstate['sum']['pre_shotoku']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 特別控除額
        $price = number_format($realEstate['sum']['privilege']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['sum']['shotoku']);
        $pdf->Cell(29, 0, $price, 0, 0, 'R');
    }

    /**
     * 譲渡の内訳書PDF出力 -　3面 - 譲渡所得金額の計算表出力 - パターン２
     * ($realEstate['show_flag']['land_house'] == true AND $realEstate['show_flag']['share_land_house'] == false)
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $realEstate
     */
    private function _putJyoto3CalculationPattern2(&$pdf, $font, $realEstate) {
        switch ($realEstate['land']['class']) {
            case '長期' :
                $pdf->RoundedRect(20.5, 242, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(20.5, 236.5, 10, 3.7, 1.5);
                break;
        }

        if ($realEstate['land']['special_law_class'] == '措') {
            $pdf->Circle(42, 238, 2);
        }

        $pdf->SetFont($font, null, 9, true);
        // 特例適用条文
        if ($realEstate['land']['special_law_class_double']) {
            if ($realEstate['land']['special_law_text'] < $realEstate['land']['special_law_text_double']) {
                $val1_top    = $realEstate['land']['special_law_text'];
                $val1_bottom = $realEstate['land']['special_law_text_double'];
                $val2_top    = $realEstate['land']['special_law_text_2'];
                $val2_bottom = $realEstate['land']['special_law_text_2_double'];
            } else {
                $val1_top    = $realEstate['land']['special_law_text_double'];
                $val1_bottom = $realEstate['land']['special_law_text'];
                $val2_top    = $realEstate['land']['special_law_text_2_double'];
                $val2_bottom = $realEstate['land']['special_law_text_2'];
            }
            $pdf->SetXY(33, 238.7);
            $pdf->Cell(8, 0, $val1_top, 0, 2, 'R');
            $pdf->SetXY(33, 241.5);
            $pdf->Cell(8, 0, $val1_bottom, 0, 0, 'R');
            $pdf->SetXY(43.7, 238.8);
            $pdf->Cell(8, 0, $val2_top, 0, 2, 'R');
            $pdf->SetXY(43.7, 241.5);
            $pdf->Cell(8, 0, $val2_bottom, 0, 2, 'R');
        } else {
            $pdf->SetXY(37, 241.4);
            $pdf->Cell(4, 0, $realEstate['land']['special_law_text'], 0, 1, 'R');
            $pdf->SetXY(42.8, 241.4);
            $pdf->Cell(9, 0, $realEstate['land']['special_law_text_2'], 0, 1, 'R');
        }
        // 収入金額
        $price = number_format($realEstate['land']['receive_sum']);
        $pdf->SetXY(53.5, 241.4);
        $pdf->Cell(28.5, 0, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['land']['total_cost']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 差引金額
        $price = number_format($realEstate['land']['pre_shotoku']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 特別控除額
        $price = number_format($realEstate['land']['privilege']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['land']['shotoku']);
        $pdf->Cell(29, 0, $price, 0, 0, 'R');


        switch ($realEstate['house']['class']) {
            case '長期' :
                $pdf->RoundedRect(20.5, 252.6, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(20.5, 247.1, 10, 3.7, 1.5);
                break;
        }

        if ($realEstate['house']['special_law_class'] == '措') {
            $pdf->Circle(42, 248.6, 2);
        }

        $pdf->SetFont($font, null, 9, true);
        // 特例適用条文
        if ($realEstate['house']['special_law_class_double']) {
            if ($realEstate['house']['special_law_text'] < $realEstate['house']['special_law_text_double']) {
                $val1_top    = $realEstate['house']['special_law_text'];
                $val1_bottom = $realEstate['house']['special_law_text_double'];
                $val2_top    = $realEstate['house']['special_law_text_2'];
                $val2_bottom = $realEstate['house']['special_law_text_2_double'];
            } else {
                $val1_top    = $realEstate['house']['special_law_text_double'];
                $val1_bottom = $realEstate['house']['special_law_text'];
                $val2_top    = $realEstate['house']['special_law_text_2_double'];
                $val2_bottom = $realEstate['house']['special_law_text_2'];
            }
            $pdf->SetXY(33, 249.3);
            $pdf->Cell(8, 0, $val1_top, 0, 2, 'R');
            $pdf->SetXY(33, 252.1);
            $pdf->Cell(8, 0, $val1_bottom, 0, 0, 'R');
            $pdf->SetXY(43.7, 249.4);
            $pdf->Cell(8, 0, $val2_top, 0, 2, 'R');
            $pdf->SetXY(43.7, 252.1);
            $pdf->Cell(8, 0, $val2_bottom, 0, 2, 'R');
        } else {
            $pdf->SetXY(37, 252);
            $pdf->Cell(4, 0, $realEstate['house']['special_law_text'], 0, 1, 'R');
            $pdf->SetXY(42.8, 252);
            $pdf->Cell(9, 0, $realEstate['house']['special_law_text_2'], 0, 1, 'R');
        }
        // 収入金額
        $price = number_format($realEstate['house']['receive_sum']);
        $pdf->SetXY(53.5, 252);
        $pdf->Cell(28.5, 0, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['house']['total_cost']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 差引金額
        $price = number_format($realEstate['house']['pre_shotoku']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 特別控除額
        $price = number_format($realEstate['house']['privilege']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['house']['shotoku']);
        $pdf->Cell(29, 0, $price, 0, 0, 'R');

    }

    /**
     * 譲渡の内訳書PDF出力 -　3面 - 譲渡所得金額の計算表出力 - パターン３
     * ($realEstate['show_flag']['land_house'] == false AND $realEstate['show_flag']['share_total'] == true)
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $realEstate
     */
    private function _putJyoto3CalculationPattern3(&$pdf, $font, $realEstate) {
        switch ($realEstate['sum']['class']) {
            case '長期' :
                $pdf->RoundedRect(20.5, 242, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(20.5, 236.5, 10, 3.7, 1.5);
                break;
        }

        if ($realEstate['sum']['special_law_class'] == '措') {
            $pdf->Circle(42, 238, 2);
        }

        $pdf->SetFont($font, null, 9, true);
        // 特例適用条文
        if ($realEstate['sum']['special_law_class_double']) {
            if ($realEstate['sum']['special_law_text'] < $realEstate['sum']['special_law_text_double']) {
                $val1_top    = $realEstate['sum']['special_law_text'];
                $val1_bottom = $realEstate['sum']['special_law_text_double'];
                $val2_top    = $realEstate['sum']['special_law_text_2'];
                $val2_bottom = $realEstate['sum']['special_law_text_2_double'];
            } else {
                $val1_top    = $realEstate['sum']['special_law_text_double'];
                $val1_bottom = $realEstate['sum']['special_law_text'];
                $val2_top    = $realEstate['sum']['special_law_text_2_double'];
                $val2_bottom = $realEstate['sum']['special_law_text_2'];
            }
            $pdf->SetXY(33, 238.7);
            $pdf->Cell(8, 0, $val1_top, 0, 2, 'R');
            $pdf->SetXY(33, 241.5);
            $pdf->Cell(8, 0, $val1_bottom, 0, 0, 'R');
            $pdf->SetXY(43.7, 238.8);
            $pdf->Cell(8, 0, $val2_top, 0, 2, 'R');
            $pdf->SetXY(43.7, 241.5);
            $pdf->Cell(8, 0, $val2_bottom, 0, 2, 'R');
        } else {
            $pdf->SetXY(37, 241.4);
            $pdf->Cell(4, 0, $realEstate['sum']['special_law_text'], 0, 1, 'R');
            $pdf->SetXY(42.8, 241.4);
            $pdf->Cell(9, 0, $realEstate['sum']['special_law_text_2'], 0, 1, 'R');
        }
        // 収入金額
        $price = number_format($realEstate['sum']['share_receive_sum']);
        $pdf->SetXY(53.5, 241.4);
        $pdf->Cell(28.5, 0, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['sum']['share_total_cost']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 差引金額
        $price = number_format($realEstate['sum']['share_pre_shotoku']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 特別控除額
        $price = number_format($realEstate['sum']['privilege']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['sum']['shotoku']);
        $pdf->Cell(29, 0, $price, 0, 0, 'R');
    }

    /**
     * 譲渡の内訳書PDF出力 -　3面 - 譲渡所得金額の計算表出力 - パターン４
     * (パタンーン１〜３以外)
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param array $realEstate
     */
    private function _putJyoto3CalculationPattern4(&$pdf, $font, $realEstate) {
        switch ($realEstate['land']['class']) {
            case '長期' :
                $pdf->RoundedRect(20.5, 242, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(20.5, 236.5, 10, 3.7, 1.5);
                break;
        }

        if ($realEstate['land']['special_law_class'] == '措') {
            $pdf->Circle(42, 238, 2);
        }

        $pdf->SetFont($font, null, 9, true);
        // 特例適用条文
        if ($realEstate['land']['special_law_class_double']) {
            if ($realEstate['land']['special_law_text'] < $realEstate['land']['special_law_text_double']) {
                $val1_top    = $realEstate['land']['special_law_text'];
                $val1_bottom = $realEstate['land']['special_law_text_double'];
                $val2_top    = $realEstate['land']['special_law_text_2'];
                $val2_bottom = $realEstate['land']['special_law_text_2_double'];
            } else {
                $val1_top    = $realEstate['land']['special_law_text_double'];
                $val1_bottom = $realEstate['land']['special_law_text'];
                $val2_top    = $realEstate['land']['special_law_text_2_double'];
                $val2_bottom = $realEstate['land']['special_law_text_2'];
            }
            $pdf->SetXY(33, 238.7);
            $pdf->Cell(8, 0, $val1_top, 0, 2, 'R');
            $pdf->SetXY(33, 241.5);
            $pdf->Cell(8, 0, $val1_bottom, 0, 0, 'R');
            $pdf->SetXY(43.7, 238.8);
            $pdf->Cell(8, 0, $val2_top, 0, 2, 'R');
            $pdf->SetXY(43.7, 241.5);
            $pdf->Cell(8, 0, $val2_bottom, 0, 2, 'R');
        } else {
            $pdf->SetXY(37, 241.4);
            $pdf->Cell(4, 0, $realEstate['land']['special_law_text'], 0, 1, 'R');
            $pdf->SetXY(42.8, 241.4);
            $pdf->Cell(9, 0, $realEstate['land']['special_law_text_2'], 0, 1, 'R');
        }
        // 収入金額
        $price = number_format($realEstate['land']['share_receive_sum']);
        $pdf->SetXY(53.5, 241.4);
        $pdf->Cell(28.5, 0, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['land']['share_total_cost']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 差引金額
        $price = number_format($realEstate['land']['share_pre_shotoku']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 特別控除額
        $price = number_format($realEstate['land']['privilege']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['land']['shotoku']);
        $pdf->Cell(29, 0, $price, 0, 0, 'R');



        switch ($realEstate['house']['class']) {
            case '長期' :
                $pdf->RoundedRect(20.5, 252.6, 10, 3.7, 1.5);
                break;
            case '短期' :
                $pdf->RoundedRect(20.5, 247.1, 10, 3.7, 1.5);
                break;
        }

        if ($realEstate['house']['special_law_class'] == '措') {
            $pdf->Circle(42, 248.6, 2);
        }

        $pdf->SetFont($font, null, 9, true);
        // 特例適用条文
        if ($realEstate['house']['special_law_class_double']) {
            if ($realEstate['house']['special_law_text'] < $realEstate['house']['special_law_text_double']) {
                $val1_top    = $realEstate['house']['special_law_text'];
                $val1_bottom = $realEstate['house']['special_law_text_double'];
                $val2_top    = $realEstate['house']['special_law_text_2'];
                $val2_bottom = $realEstate['house']['special_law_text_2_double'];
            } else {
                $val1_top    = $realEstate['house']['special_law_text_double'];
                $val1_bottom = $realEstate['house']['special_law_text'];
                $val2_top    = $realEstate['house']['special_law_text_2_double'];
                $val2_bottom = $realEstate['house']['special_law_text_2'];
            }
            $pdf->SetXY(33, 249.3);
            $pdf->Cell(8, 0, $val1_top, 0, 2, 'R');
            $pdf->SetXY(33, 252.1);
            $pdf->Cell(8, 0, $val1_bottom, 0, 0, 'R');
            $pdf->SetXY(43.7, 249.4);
            $pdf->Cell(8, 0, $val2_top, 0, 2, 'R');
            $pdf->SetXY(43.7, 252.1);
            $pdf->Cell(8, 0, $val2_bottom, 0, 2, 'R');
        } else {
            $pdf->SetXY(37, 252);
            $pdf->Cell(4, 0, $realEstate['house']['special_law_text'], 0, 1, 'R');
            $pdf->SetXY(42.8, 252);
            $pdf->Cell(9, 0, $realEstate['house']['special_law_text_2'], 0, 1, 'R');
        }
        // 収入金額
        $price = number_format($realEstate['house']['share_receive_sum']);
        $pdf->SetXY(53.5, 252);
        $pdf->Cell(28.5, 0, $price, 0, 0, 'R');
        // 必要経費
        $price = number_format($realEstate['house']['share_total_cost']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 差引金額
        $price = number_format($realEstate['house']['share_pre_shotoku']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 特別控除額
        $price = number_format($realEstate['house']['privilege']);
        $pdf->Cell(28.2, 0, $price, 0, 0, 'R');
        // 譲渡所得金額
        $price = number_format($realEstate['house']['shotoku']);
        $pdf->Cell(29, 0, $price, 0, 0, 'R');
    }

    /**
     * 買換資産の明細書PDF出力
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     */
    function export_jyoto_kaikae($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'kaikae_meisai.pdf');
        $ReakEstate = ClassRegistry::init('RealEstate');
        $realEstate = $ReakEstate->findForDetail();

        $pdf->SetFillColor(200);
        $pdf->SetLeftMargin(12.5);

        // 住所
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(62.5, 49);
        $address = h($realEstate['NameList']['prefecture']. $realEstate['NameList']['city']. $realEstate['NameList']['address']);
        $address = $this->roundLineStrByWidthNot($address, 78, 3);
        $pdf->MultiCell(430, 21, $address, 0, 'L', false, 0);

        // 氏名
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->SetXY(58,63.7);
        $frigana = h($realEstate['NameList']['name_furigana']);
        $frigana = $this->roundLineStrByWidthNot($frigana, 52, 1);
        $pdf->Cell(50, 4, $frigana, 0, 2, 'L');

        $pdf->SetXY(59,70.5);
        $pdf->SetFont($font, null, 10, true);
        $name = h($realEstate['NameList']['name']);
        $name = $this->roundLineStrByWidthNot($name, 76,1);
        $pdf->MultiCell(70, 10.8, $name, 0, 'L', false, 0);

        // 電話番号
        $pdf->SetFont($font, null, 11, true);
        if ($realEstate['NameList']['phone_number']) {
            $numbers = explode('-', $realEstate['NameList']['phone_number']);
            $pdf->SetXY(155.5, 63);
            $pdf->Cell(11, 0, $numbers[0], 0, 2, 'C');
            $pdf->SetXY(157, 71);
            $pdf->Cell(36, 0, $numbers[1] . '-' . $numbers[2], 0, 2, 'C');
        }

        //特例適用条文
        $pdf->RoundedRect(30.5, 106.7, 33, 3.7, 1.5);

        $pdf->SetXY(72.5,108.7);
        $pdf->Cell(50, 4, '36', 0, 2, 'L');

        $pdf->SetXY(84.2,108.7);
        $pdf->Cell(50, 4, 'の2', 0, 2, 'L');

        // 建物所在地
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(60, 128);
        $address = h($realEstate['RealEstate']['sale_prefecture'] . $realEstate['RealEstate']['sale_city'] . $realEstate['RealEstate']['sale_address']);
        $address = $this->roundLineStrByWidthNot($address, 78, 2);
        $pdf->MultiCell(430, 21, $address, 0, 'L', false, 0);

        //資産の種類　数量
        if($realEstate['RealEstate']['house_area']){
          // 建物面積
          $pdf->SetXY(77, 139);
          $pdf->Cell(15, 8.5, '建物 / 土地', 0, 0, 'C');
          $pdf->SetXY(173, 139);
          $both_area = $realEstate['RealEstate']['house_area'].' ㎡'.' / '.$realEstate['RealEstate']['land_area'];
          $pdf->Cell(15, 8.5, $both_area, 0, 0, 'R');
        } else {
          // 土地面積
          $pdf->SetXY(78, 139);
          $pdf->Cell(15, 8.5, '土地', 0, 0, 'C');
          $pdf->SetXY(170, 139);
          $pdf->Cell(15, 8.5, $realEstate['RealEstate']['land_area'], 0, 0, 'R');
        }

        // 譲渡した年月日
        $date = $realEstate['RealEstate']['real_sale_date'];
        $date = date('Y-n-j', strtotime($date));
        $pdf->SetXY(148, 151);
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(13, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(13, 8, $dates[2], 0, 0, 'R');
        }


        // 譲渡価額
        $pdf->SetXY(70, 151);
        $price = number_format($realEstate['sum']['receive_sum']);
        $pdf->Cell(33, 7.85, $price, 0, 0, 'R');

        //資産の種類
        $pdf->SetXY(77, 172.5);
        $stock_class = $realEstate['sum']['maybe_get_stock'] ;
        $pdf->Cell(15, 8.5, $stock_class, 0, 0, 'C');

        //数量

        $pdf->SetXY(173, 172.5);
        $both_area = $realEstate['sum']['get_area'];
        $pdf->Cell(15, 8.5, $both_area, 0, 0, 'R');

        // 取得した年月日
        $date = $realEstate['NextRealEstate'][0]['house_get_date'];
        $date = date('Y-n-j', strtotime($date));
        $pdf->SetXY(142, 219.3);
        if ($date) {
            $dates = explode('-', $date);
            $pdf->Cell(14.5, 8, $dates[0], 0, 0, 'R');
            $pdf->Cell(15, 8, $dates[1], 0, 0, 'R');
            $pdf->Cell(15, 8, $dates[2], 0, 0, 'R');
        }

        //取得見積額
        $get_sum = $realEstate['sum']['maybe_pay_sum'];
        $get_sum = number_format($get_sum);
        $pdf->SetXY(70, 219.3);
        $pdf->Cell(33, 7.85, $get_sum, 0, 0, 'R');

        // 税理士
        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY(54, 265.7);
        $name = $realEstate['User']['tax_accountant_name'];
        $pdf->Cell(86, 5, $name, 0, 0, 'L');


        $pdf->SetXY(159, 266.8);
        $pdf->SetFont($font, null, 8, true);
        $phone = $realEstate['User']['tax_accountant_phone'];
        $pdf->Cell(35, 3, $phone, 0, 0, 'C');

        return $pdf;
    }

    /**
     * 固定資産
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @param $search_words
     * @return FPDI OBJ $pdf
     */
    function export_shareholders_list($pdf, $font, $search_words = array()) {
        // ユーザ情報取得
        $this->Controller->set('name', CakeSession::read('Auth.User.name'));
        $term_id = CakeSession::read('Auth.User.term_id');
        $user_id = CakeSession::read('Auth.User.id');

        $datas = ClassRegistry::init('Shareholder')->getShareholdersList($user_id, $term_id);
        $this->Controller->set('datas', $datas);

        // helper読込
        $this->Controller->helpers[] = 'Pdf';
        // View設定
        $this->Controller->layout = 'pdf';
        // 日本語対応＆A4横で出力
        $this->Mpdf->init(array('mode'=>'ja', 'format'=>'A4-L'));

        $pdf_name = TMP.time().'.pdf';
        $this->Mpdf->setFilename($pdf_name);
        // ファイルに出力
        $this->Mpdf->setOutput('F');

        $this->Controller->render('/Shareholders/export_pdf_for_menber');
        $html = (string)$this->Controller->response;
        $this->Mpdf->outPut($html);
        // ２重に出力されないように出力タイプ変更
        $this->Mpdf->setOutput('S');

        //テンプレート読込
        $pageCount = $pdf->setSourceFile($pdf_name);
        for ($pageNo = 1;  $pageNo <= $pageCount; $pageNo++) {
            $template = $pdf->importPage($pageNo);
            //ページ追加
            $pdf->AddPage('L');
            $pdf->useTemplate($template, null, null, null, null, true);
        }

        // ファイル削除
        unlink($pdf_name);

        return $pdf;
    }

    /**
     * 設立時貸借対照表
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_establishment_balance_sheet($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'establishment_balance_sheet.pdf');
        $pdf->deletePage($pdf->PageNo());
        // データ取り出し
        $datas = ClassRegistry::init('EstablishmentBalanceSheet')->findForIndex();

        $height = 50;
        $line = 50;

        foreach($datas as $big_group_name => $big_group) {
          if ($big_group_name == 'User') continue;
          $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
          // BigName
          $pdf->SetFont($font, null, 12, true);
          $pdf->SetXY(70, $height + $line * 7);
          $pdf->cell(72, 6, $big_group_name. 'の部', 0, 0, 'C');
          $line++;

          foreach($big_group as $middle_group_name => $middle_group) {
            if ($middle_group_name == 'sum') continue;
            $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
            // MiddleName
            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(20, $height + $line * 7);
            $pdf->cell(40, 6, '【'. $middle_group_name. '】', 0, 0, 'L');
            $line++;

            foreach($middle_group as $small_group_name => $small_group) {
              if ($small_group_name == 'sum') continue;
              $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
              if (!($small_group_name == '現金・預金' or $small_group_name == '資本金')) {
                // SumallName
                $pdf->SetFont($font, null, 11, true);
                if ($small_group_name == '有形固定資産' || $small_group_name == '無形固定資産' || $small_group_name == '投資その他の資産' ) {
                  $pdf->SetXY(22, $height + $line * 7);
                  $pdf->cell(40, 6, '（'. $small_group_name. '）', 0, 0, 'L');
                  $line++;
                } else if($small_group_name == '資本剰余金' ||$small_group_name == '利益剰余金' ) {
                  $pdf->SetXY(27, $height + $line * 7);
                  $this->equal_cell_space($pdf, 6, $small_group_name, str_repeat('あ', 12));
                  $line++;
                }

              }

              foreach($small_group as $individual) {
                $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
                // Name
                debug($individual);
                $pdf->SetFont($font, null, 11, true);
                if ($small_group_name == '資本剰余金' ||$small_group_name == '利益剰余金' ) {
                  $pdf->SetXY(35, $height + $line * 7);
                } else {
                  $pdf->SetXY(27, $height + $line * 7);
                }
                $this->equal_cell_space($pdf, 6, $individual['AccountTitle']['closing_account_title'], str_repeat('あ', 12));

                // sum
                $pdf->SetFont($font, null, 11, true);
                $pdf->SetXY(165, $height + $line * 7);
                $pdf->cell(30, 6, number_format($individual['EstablishmentBalanceSheet']['account_sum']), 0, 0, 'R');

                $line++;
              }
            }

            $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
            // middle sum
            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(55, $height + $line * 7);
            $this->equal_cell_space($pdf, 6, $middle_group_name. '合計', str_repeat('あ', 12));

            $pdf->SetFont($font, null, 11, true);
            $pdf->SetXY(165, $height + $line * 7);
            $pdf->cell(30, 6, number_format($middle_group['sum']['EstablishmentBalanceSheet']['sumAccountSum']), 'B', 0, 'R');

            $line++;
            $line++;
          }

          $line--;
          $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
          // big sum
          $pdf->SetFont($font, null, 11, true);
          $pdf->SetXY(60, $height + $line * 7);
          $this->equal_cell_space($pdf, 6, $big_group_name. 'の部合計', str_repeat('あ', 12));

          $pdf->SetFont($font, null, 11, true);
          $pdf->SetXY(165, $height + $line * 7);
          $pdf->cell(30, 6, number_format($big_group['sum']['EstablishmentBalanceSheet']['sumAccountSum']), 'B', 0, 'R');

          if ($big_group_name == '資産') {
            $pdf->Line(165, $height + $line * 7 + 6.5, 195, $height + $line * 7 + 6.5);
          }

          $line++;
          $line++;
        }

        $line--;
        $line = $this->check_establishment_balance_sheet($pdf, $line, $template, $datas['User']);
        $pdf->SetFont($font, null, 11, true);
        $pdf->SetXY(60, $height + $line * 7);
        $this->equal_cell_space($pdf, 6, '負債及び純資産合計', str_repeat('あ', 12));

        $pdf->SetFont($font, null, 11, true);
        $pdf->SetXY(165, $height + $line * 7);
        $pdf->cell(30, 6, number_format($big_group['sum']['EstablishmentBalanceSheet']['sumAccountSum']), 'B', 0, 'R');
        $pdf->Line(165, $height + $line * 7 + 6.5, 195, $height + $line * 7 + 6.5);

        return $pdf;
    }

    private function check_establishment_balance_sheet(&$pdf, $line, $template, $user) {
      if ($line >= 32) {
        //ページ追加
        $pdf->AddPage();
        $pdf->useTemplate($template, null, null, null, null, true);

        $pdf->SetFont($font, null, 10.5, true);
        // 年月日
        $pdf->SetXY(83, 30);
        $date = date('Y 年 n 月 j 日　現在', strtotime($user['establishment_date']));
        $pdf->cell(30, 8, $date);

        // ユーザ名
        $pdf->SetXY(27, 35);
        $pdf->cell(30, 8, $user['name']);

        $line = 0;
      }
      return $line;
    }

    /**
    * セル幅を利用した文字数幅での均等割付を行う
    *
    * @param  object $pdf     帳票オブジェクト
    * @param  int    $height  高さ
    * @param  string $str     割付する文字列
    * @param  string $str_len 最大文字幅での文字列
    * @return object
    */

    private function equal_cell_space($pdf, $height, $str, $str_len) {
      mb_internal_encoding('UTF-8');

      // 最大文字幅と1文字分の文字幅を取得する
      list($total_width, $char_width) = $this->cell_width($pdf, $str_len);

      if ("{$str}" == "" or mb_strlen($str) == 1) {
        $pdf->Cell($total_width, $height, $str, 0, 0);
        return $pdf;
      }

      //最後の文字以外の文字幅を算出する（最後の文字幅は1文字分ピッタリにするため、算出不要）
      $char_num   = mb_strlen($str) - 1;
      $cell_width = ($total_width - $char_width) / $char_num;

      // 1文字づつセルに入れていく
      for ($i = 0; $i < $char_num; $i++) {
        $pdf->Cell($cell_width, $height, mb_substr($str, $i, 1), 0, 0);
      }
      $pdf->Cell($char_width, $height, mb_substr($str, $char_num, 1), 0, 0);

      return $pdf;
    }
    /**
    * 指定した文字列の文字幅と1文字分の文字幅を算出する
    *
    * @param  object $pdf     FPDFオブジェクト
    * @param  string $str_len
    * @return array           array(文字列の文字幅,1文字分の文字幅)
    */

    private function cell_width($pdf, $str_len) {

      $total_length = $pdf->GetStringWidth($str_len);
      $char_length  = $pdf->GetStringWidth(mb_substr($str_len, 0, 1));

      //-- 長さを適当に丸める
      // 長さが整数でない場合、小数点以下で切り上げる（ちょっと長めにするため）
      $total_length = (((int)$total_length) < $total_length) ? (int)($total_length + 1) : (int)$total_length;
      // 1文字分の長さは四捨五入にする。
      $_char_length = round($char_length, 1);
      // 四捨五入によって、元々の文字の長さより算出結果が短くならないようにする。
      $char_length  = ($char_length > $_char_length) ? ($_char_length + 0.1 ) : $_char_length;

      return array($total_length, $char_length);
    }

    /**
     * 設立届
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_establishment_notification($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'establishment_notification.pdf');

        $datas = ClassRegistry::init('EstablishmentNotification')->findForPDF();

        //提出年月日
        $pdf->SetFont($font, null, 9, true);
        $modified = $this->convertHeiseiDate($datas['EstablishmentNotification']['hand_in_date']);

        $pdf->SetXY(32.5, 44.1);
        $pdf->Cell(8, 0, $modified['year'], 0, 0, 'C');
        $pdf->SetXY(41.2, 44.1);
        $pdf->Cell(8, 0, $modified['month'], 0, 0, 'C');
        $pdf->SetXY(49.7, 44.1);
        $pdf->Cell(8, 0, $modified['day'], 0, 0, 'C');

        //税務署
        $pdf->SetFont($font, null, 12.5, true);
        $pdf->SetXY(20, 67.1);
        $pdf->Cell(30, 0,$datas['User']['tax_office'], 0,0,'R');

        // 郵便番号
        $result = substr_replace($datas['User']['NameList']['post_number'], "-", 3, 0);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(110, 22.5);
        $pdf->Cell(40, 0, $result, 0);
        // 住所
        $address = $datas['User']['NameList']['prefecture']. $datas['User']['NameList']['city']. $datas['User']['NameList']['address'];
        $address = mb_substr($address,0,62,'utf-8');
        $pdf->setCellHeightRatio(1.1);
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->SetXY(106, 23);
        $pdf->MultiCell(85, 4, $address, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 電話番号
        $phone = explode('-', $datas['User']['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(129, 31.3);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(140, 31.3);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(156, 31.3);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        // 納税地郵便番号
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(110, 35.5);
        $result = substr_replace($datas['User']['NameList']['post_number'], "-", 3, 0);
        $pdf->Cell(40, 0, $result, 0);
        // 住所
        $address = $datas['User']['NameList']['prefecture']. $datas['User']['NameList']['city']. $datas['User']['NameList']['address'];
        $address = mb_substr($address,0,62,'utf-8');
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->SetXY(106, 36);
        $pdf->MultiCell(85, 4, $address, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // フリガナ
        $pdf->SetFont($font, null, 7.3, true);
        $pdf->SetXY(105, 45.6);
        $name = $datas['User']['NameList']['name_furigana'];
        $name = mb_substr($name,0,32,'utf-8');
        $pdf->Cell(86, 0,$name , 0);
        // 法人名
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(105, 48.5);
        $pdf->MultiCell(85, 4, $datas['User']['NameList']['name'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 法人番号
        $pdf->SetFont($font, null, 12, true);
        $numbers = preg_split("//u", $datas['User']['company_number'], -1, PREG_SPLIT_NO_EMPTY);
        foreach($numbers as $n_key => $numver) {
          $pdf->SetXY(106.5 + $n_key * 6.5, 62.7);
          $pdf->Cell(86, 0, $numver, 0);
        }

        // 代表者フリガナ
        $pdf->SetFont($font, null, 7.3, true);
        $pdf->SetXY(105, 69.3);
        $name = $datas['president']['NameList']['name_furigana'];
        $name = mb_substr($name,0,32,'utf-8');
        $pdf->Cell(86, 0,$name , 0);
        // 代表者名
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(105, 72);
        $name = $datas['president']['NameList']['name'];
        $name = mb_substr($name,0,44,'utf-8');
        $pdf->MultiCell(76, 4,$name, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        //$pdf->MultiCell(74, 4, str_repeat('あ',42), 1, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 代表者郵便番号
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(110, 83);
        $result = substr_replace($datas['president']['NameList']['post_number'], "-", 3, 0);
        $pdf->Cell(40, 0, $result, 0);
        // 代表者住所
        $address = $datas['president']['NameList']['prefecture']. $datas['president']['NameList']['city']. $datas['president']['NameList']['address'];
        $address = mb_substr($address,0,31,'utf-8');
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->SetXY(106, 86);
        $pdf->Cell(40, 0, $address, 0);

        // 代表者電話番号
        $phone = explode('-', $datas['president']['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(129, 89);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(140, 89);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(156, 89);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        // 設立年月日
        $_date = strtotime($datas['User']['establishment_date']);
        $y = date('Y', $_date) - 1988;
        if ($y == '1') {
          $y = '元';
        }
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(57, 96);
        $pdf->Cell(5, 0, $y, 0, 0, 'C');
        $pdf->SetXY(68, 96);
        $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
        $pdf->SetXY(80, 96);
        $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');

        // 事業年度
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(134, 96);
        $pdf->Cell(5, 0, $datas['EstablishmentNotification']['period_start_month'], 0, 0, 'C');
        $pdf->SetXY(146, 96);
        $pdf->Cell(5, 0, $datas['EstablishmentNotification']['period_start_day'], 0, 0, 'C');
        $pdf->SetXY(166, 96);
        $pdf->Cell(5, 0, $datas['EstablishmentNotification']['period_end_month'], 0, 0, 'C');
        $pdf->SetXY(178, 96);
        $pdf->Cell(5, 0, $datas['EstablishmentNotification']['period_end_day'], 0, 0, 'C');

        // 出資金
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(55, 104);
        $pdf->Cell(32, 0, number_format($datas['User']['capital_sum']), 0, 0, 'R');

        // 事業年度開始
        if($datas['EstablishmentNotification']['new_company_date']){
          $_date = strtotime($datas['EstablishmentNotification']['new_company_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(159, 104);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(168, 104);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(176.5, 104);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }

        // 事業の目的
        $pdf->SetFont($font, null, 8.5, true);
        $pdf->SetXY(28, 115);
        $pdf->Cell(64, 0, $datas['EstablishmentNotification']['business_porpose_paper1'], 0);
        $pdf->SetXY(28, 119);
        $pdf->Cell(64, 0, $datas['EstablishmentNotification']['business_porpose_paper2'], 0);
        $pdf->SetXY(28, 123);
        $pdf->Cell(64, 0, $datas['EstablishmentNotification']['business_porpose_paper3'], 0);

        $pdf->SetXY(28, 132);
        $pdf->Cell(64, 0, $datas['EstablishmentNotification']['business_porpose_real1'], 0);
        $pdf->SetXY(28, 136);
        $pdf->Cell(64, 0, $datas['EstablishmentNotification']['business_porpose_real2'], 0);
        $pdf->SetXY(28, 140);
        $pdf->Cell(64, 0, $datas['EstablishmentNotification']['business_porpose_real3'], 0);

        // 支店名称
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(99.5, 115.5);
        $pdf->MultiCell(27, 4, $datas['EstablishmentNotification']['branch_name1'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(99.5, 122.5);
        $pdf->MultiCell(27, 4, $datas['EstablishmentNotification']['branch_name2'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(99.5, 130);
        $pdf->MultiCell(27, 4, $datas['EstablishmentNotification']['branch_name3'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(99.5, 137.5);
        $pdf->MultiCell(27, 4, $datas['EstablishmentNotification']['branch_name4'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(125, 115.5);
        $pdf->MultiCell(65, 4, $datas['EstablishmentNotification']['branch_address1'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(125, 122.5);
        $pdf->MultiCell(65, 4, $datas['EstablishmentNotification']['branch_address2'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(125, 130);
        $pdf->MultiCell(65, 4, $datas['EstablishmentNotification']['branch_address3'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(125, 137.5);
        $pdf->MultiCell(65, 4, $datas['EstablishmentNotification']['branch_address4'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 設立の形態
        $pdf->SetFont($font, null, 12, true);
        for ($i = 0; $i < 5; $i++) {
          if ($datas['EstablishmentNotification']['establishment_form'] == $i+1) {
            $pdf->SetXY(51.5, 145.6 + $i * 3.5);
            $pdf->Cell(5, 0, '○', 0);
          }
        }
        $pdf->SetFont($font, null, 9.5, true);
        for ($i = 0; $i < 3; $i++) {
          if ($datas['EstablishmentNotification']['form_split_more'] == $i+1) {
            $pdf->SetXY(112 + $i * 14.3, 152.7);
            $pdf->Cell(5, 0, '✓', 0);
          }
        }
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(68, 160.3);
        $pdf->Cell(40, 0, $datas['EstablishmentNotification']['establishment_form_other'], 0, 0, 'C');

        // 状況
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(51, 168.5);
        $pdf->MultiCell(49, 4, $datas['EstablishmentNotification']['previous_form_name'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(51, 176.3);
        $pdf->MultiCell(49, 4, $datas['EstablishmentNotification']['previous_form_name2'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(99, 168.5);
        $pdf->MultiCell(49, 4, $datas['EstablishmentNotification']['previous_form_address'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(99, 176.3);
        $pdf->MultiCell(49, 4, $datas['EstablishmentNotification']['previous_form_address2'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(148, 168.5);
        $pdf->MultiCell(43, 4, $datas['EstablishmentNotification']['previous_form_content'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $pdf->SetXY(148, 176.3);
        $pdf->MultiCell(43, 4, $datas['EstablishmentNotification']['previous_form_content2'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 適格区分
        if ($datas['EstablishmentNotification']['eligible'] == 1) {
          $pdf->SetXY(99.5, 187.5);
          $pdf->Cell(14, 0, '', 1);
        } elseif ($datas['EstablishmentNotification']['eligible'] == 2) {
          $pdf->SetXY(117.5, 187);
          $pdf->Cell(14, 0, '', 1);
        }

        // 事業開始見込み
        if($datas['EstablishmentNotification']['business_start_date']){
          $_date = strtotime($datas['EstablishmentNotification']['business_start_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(84, 194.8);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(98, 194.8);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(113, 194.8);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }

        // 提出の有無
        $pdf->SetFont($font, null, 13.5, true);
        if ($datas['EstablishmentNotification']['salary_start_class'] == 1) {
          $pdf->SetXY(84.2, 201.6);
          $pdf->Cell(5, 0, '○', 0);
        } elseif ($datas['EstablishmentNotification']['salary_start_class'] == 2) {
          $pdf->SetXY(107, 201.6);
          $pdf->Cell(5, 0, '○', 0);
        }

        // 添付書類
        $pdf->SetFont($font, null, 12, true);
        if ($datas['EstablishmentNotification']['attached_document_teikan'] == 1) {
          $pdf->SetXY(139.5, 187);
          $pdf->Cell(5, 0, '○', 0);
        }
        if ($datas['EstablishmentNotification']['attached_document_stock'] == 1) {
          $pdf->SetXY(139.5, 190.7);
          $pdf->Cell(5, 0, '○', 0);
        }
        if ($datas['EstablishmentNotification']['attached_document_porpose'] == 1) {
          $pdf->SetXY(139.5, 194.4);
          $pdf->Cell(5, 0, '○', 0);
        }
        if ($datas['EstablishmentNotification']['attached_document_bs'] == 1) {
          $pdf->SetXY(139.5, 198.1);
          $pdf->Cell(5, 0, '○', 0);
        }
        if ($datas['EstablishmentNotification']['attached_document_merger'] == 1) {
          $pdf->SetXY(139.5, 201.8);
          $pdf->Cell(5, 0, '○', 0);
        }
        if ($datas['EstablishmentNotification']['attached_document_split'] == 1) {
          $pdf->SetXY(139.5, 205.5);
          $pdf->Cell(5, 0, '○', 0);
        }
        if ($datas['EstablishmentNotification']['attached_document_other'] == 1) {
          $pdf->SetXY(139.5, 209);
          $pdf->Cell(5, 0, '○', 0);
        }
        $pdf->SetFont($font, null, 8.5, true);
        $pdf->SetXY(156, 209.5);
        $pdf->Cell(30, 0, $datas['EstablishmentNotification']['attached_document_other_content'], 0, 0, 'C');

        // 関与税理士
        $pdf->SetFont($font, null, 8.5, true);
        $pdf->SetXY(68, 210.7);
        $pdf->Cell(60, 0, $datas['tax_accountant']['NameList']['name'], 0);
        $address = $datas['tax_accountant']['NameList']['prefecture']. $datas['tax_accountant']['NameList']['city']. $datas['tax_accountant']['NameList']['address'];
        $pdf->SetXY(68, 216);
        $pdf->MultiCell(63, 4, $address, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $phone = explode('-', $datas['tax_accountant']['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(90.5, 225.8);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(103, 225.8);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(120, 225.8);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        // 連結親法人名
        $pdf->SetFont($font, null, 8.5, true);
        $pdf->SetXY(65, 230.5);
        $pdf->MultiCell(96, 4, $datas['NameList']['name'], 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        $pdf->SetXY(65, 240.7);
        $result = substr_replace($datas['NameList']['post_number'], "-", 3, 0);
        $pdf->Cell(20, 4, $result, 0);
        $address = $datas['NameList']['prefecture']. $datas['NameList']['city']. $datas['NameList']['address'];
        $pdf->SetXY(65, 241.5);
        $pdf->MultiCell(96, 4, $address, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        $phone = explode('-', $datas['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(107, 250);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(117, 250);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(132, 250);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        // 所轄税務署
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(171, 247.8);
        $pdf->Cell(17, 0, $datas['EstablishmentNotification']['linking_tax_office'], 0, 0, 'C');

        // 連結親法人提出日
        if($datas['EstablishmentNotification']['linking_parent_date']){
          $_date = strtotime($datas['EstablishmentNotification']['linking_parent_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(145, 259);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(152.3, 259);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(160, 259);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }
        // 連結子法人提出日
        if($datas['EstablishmentNotification']['linking_child_date']){
          $_date = strtotime($datas['EstablishmentNotification']['linking_child_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9, true);
          $pdf->SetXY(169, 259);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(175.6, 259);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(181.1, 259);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }

        return $pdf;
    }

    /**
     * 青色申告承認申請
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function blue_color($pdf, $font) {
        $template  = $this->setTemplateAddPage($pdf, $font, 'enter_blue_color.pdf');

        $datas = ClassRegistry::init('BlueColor')->findForPDF();

        $pdf->setCellHeightRatio(1.1);

        //提出年月日
        $pdf->SetFont($font, null, 9, true);
        $modified = $this->convertHeiseiDate($datas['BlueColor']['hand_in_date']);

        $pdf->SetXY(37.2, 50.3);
        $pdf->Cell(8, 0, $modified['year'], 0, 0, 'C');
        $pdf->SetXY(46.2, 50.3);
        $pdf->Cell(8, 0, $modified['month'], 0, 0, 'C');
        $pdf->SetXY(54.7, 50.3);
        $pdf->Cell(8, 0, $modified['day'], 0, 0, 'C');

        //税務署
        $pdf->SetFont($font, null, 12.5, true);
        $pdf->SetXY(25, 95);
        $pdf->Cell(30, 0,$datas['User']['tax_office'], 0,0,'R');

        // 郵便番号
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(125, 28.8);
        $pdf->Cell(40, 0, $datas['NameList']['post_number'], 0);
        // 住所
        $address = $datas['NameList']['prefecture']. $datas['NameList']['city']. $datas['NameList']['address'];
        $address = mb_substr($address,0,48,'utf-8');
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->SetXY(121, 30);
        $pdf->MultiCell(67, 4, $address, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 電話番号
        $phone = explode('-', $datas['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(144, 38.5);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(155, 38.5);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(170, 38.5);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        // フリガナ
        $pdf->SetFont($font, null, 7.3, true);
        $pdf->SetXY(121, 43.5);
        $name_furigana = mb_substr($datas['NameList']['name_furigana'],0,24,'utf-8');
        $name_furigana = mb_convert_kana($name_furigana, "KVC","UTF-8");
        $pdf->Cell(86, 0, $name_furigana, 0);
        // 法人名
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(121, 47);
        $name = mb_substr($datas['NameList']['name'],0,38,'utf-8');
        $pdf->MultiCell(66, 4, $name, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 法人番号
        $pdf->SetFont($font, null, 12, true);
        $numbers = preg_split("//u", $datas['User']['company_number'], -1, PREG_SPLIT_NO_EMPTY);
        foreach($numbers as $n_key => $numver) {
          $pdf->SetXY(121 + $n_key * 5.1, 61);
          $pdf->Cell(86, 0, $numver, 0);
        }

        // 代表者フリガナ
        $pdf->SetFont($font, null, 7.3, true);
        $pdf->SetXY(121, 67);
        $name_furigana = mb_substr($datas['President']['name_furigana'],0,24,'utf-8');
        $name_furigana = mb_convert_kana($name_furigana, "KVC","UTF-8");
        $pdf->Cell(86, 0, $name_furigana, 0);
        // 代表者名
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(121, 69.5);
        $name = mb_substr($datas['President']['name'],0,32,'utf-8');
        $pdf->MultiCell(55, 4, $name, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);
        //$pdf->MultiCell(50, 4, str_repeat('あ',28), 1, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 代表者郵便番号
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(125, 80);
        $pdf->Cell(40, 0, $datas['President']['post_number'], 0);
        // 代表者住所
        $address = $datas['President']['prefecture']. $datas['President']['city']. $datas['President']['address'];
        $pdf->SetFont($font, null, 7.5, true);
        $pdf->SetXY(121, 81);
        $address = mb_substr($address,0,48,'utf-8');
        $pdf->MultiCell(68, 4, $address, 0, 'L', 0, 1, '', '', true, 0, false, true, 11, 'M', true);

        // 事業種目
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(130, 90.3);
        $pdf->Cell(40, 0, $datas['User']['business'], 0, 0, 'C');

        // 資本金
        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(138, 97);
        $pdf->Cell(40, 0, number_format($datas['User']['capital_sum']), 0, 0, 'R');

        // 自
        $_date = strtotime($datas['BlueColor']['start_period_date']);
        $y = date('Y', $_date) - 1988;
        if ($y == '1') {
          $y = '元';
        }
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(42, 105.3);
        $pdf->Cell(5, 0, $y, 0, 0, 'C');
        $pdf->SetXY(51, 105.3);
        $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
        $pdf->SetXY(60, 105.3);
        $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');

        // 至
        $_date = strtotime($datas['BlueColor']['end_period_date']);
        $y = date('Y', $_date) - 1988;
        if ($y == '1') {
          $y = '元';
        }
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(42, 113.8);
        $pdf->Cell(5, 0, $y, 0, 0, 'C');
        $pdf->SetXY(51, 113.8);
        $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
        $pdf->SetXY(60, 113.8);
        $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');

        // チェック１
        if ($datas['BlueColor']['again_reason'] == '1') {
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(32, 129);
          $pdf->Cell(5, 0, '✓', 0, 0, 'C');

          // 年月日
          $_date = strtotime($datas['BlueColor']['again_fact_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9.5, true);
          $pdf->SetXY(157.5, 136.7);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(166, 136.7);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(175, 136.7);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }
        // チェック2
        if ($datas['BlueColor']['again_reason2'] == '1') {
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(32, 143);
          $pdf->Cell(5, 0, '✓', 0, 0, 'C');

          // 年月日
          $_date = strtotime($datas['BlueColor']['again_fact_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9.5, true);
          $pdf->SetXY(157.5, 157.7);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(166, 157.7);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(175, 157.7);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }
        // チェック3
        if ($datas['BlueColor']['again_reason3'] == '1') {
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(32, 163.4);
          $pdf->Cell(5, 0, '✓', 0, 0, 'C');

          // 年月日
          $_date = strtotime($datas['BlueColor']['again_fact_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9.5, true);
          $pdf->SetXY(157.5, 167.2);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(166, 167.2);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(175, 167.2);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }
        // チェック4
        if ($datas['BlueColor']['again_reason4'] == '1') {
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(32, 173.3);
          $pdf->Cell(5, 0, '✓', 0, 0, 'C');

          // 年月日
          $_date = strtotime($datas['BlueColor']['again_fact_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9.5, true);
          $pdf->SetXY(157.9, 181.2);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(166.4, 181.2);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(175.1, 181.2);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }
        // チェック5
        if ($datas['BlueColor']['again_reason5'] == '1') {
          $pdf->SetFont($font, null, 10, true);
          $pdf->SetXY(32, 187.5);
          $pdf->Cell(5, 0, '✓', 0, 0, 'C');

          // 年月日
          $_date = strtotime($datas['BlueColor']['again_fact_date']);
          $y = date('Y', $_date) - 1988;
          if ($y == '1') {
            $y = '元';
          }
          $pdf->SetFont($font, null, 9.5, true);
          $pdf->SetXY(157.5, 191.7);
          $pdf->Cell(5, 0, $y, 0, 0, 'C');
          $pdf->SetXY(166, 191.7);
          $pdf->Cell(5, 0, date('n', $_date), 0, 0, 'C');
          $pdf->SetXY(175, 191.7);
          $pdf->Cell(5, 0, date('j', $_date), 0, 0, 'C');
        }

        $pdf->SetFont($font, null, 8.5, true);
        $count = 1;
        $w_count = 0;
        $h_count = 0;
        for($i = 1; $i <= 6; $i++) {
          if ($i == 4) {
            $h_count = 0;
            $w_count++;
          }
          // 帳簿名
          $pdf->SetXY(26.5 + $w_count * 80, 218.3 + $h_count * 6.1);
          $pdf->Cell(38, 0, $datas['BlueColor']['book_name'.$i], 0, 0, 'C');
          // 形態
          $pdf->SetXY(66 + $w_count * 80, 218.3 + $h_count * 6.1);
          $pdf->Cell(20, 0, $datas['BlueColor']['book_form'.$i], 0, 0, 'C');
          // 時期
          $pdf->SetXY(87 + $w_count * 80, 218.3 + $h_count * 6.1);
          $pdf->Cell(18, 0, $datas['BlueColor']['book_when'.$i], 0, 0, 'C');

          $h_count++;
        }

        // 特別な記帳方法
        if ($datas['BlueColor']['special_book_keeping'] == 1) {
          $pdf->SetFont($font, null, 12, true);
          $pdf->SetXY(33.2, 239.3);
          $pdf->Cell(5, 0, '○', 0, 0, 'C');
        }
        if ($datas['BlueColor']['special_book_keeping'] == 2) {
          $pdf->SetFont($font, null, 12, true);
          $pdf->SetXY(33.2, 242.9);
          $pdf->Cell(5, 0, '○', 0, 0, 'C');
        }

        // 関与度合い
        $pdf->SetFont($font, null, 8, true);
        $pdf->SetXY(36.5, 254.5);
        $pdf->Cell(142, 0, $datas['BlueColor']['tax_accountant_doing_what'], 0);

        return $pdf;
    }

    /**
     *
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_salary_notification($pdf, $font) {
    ini_set("mbstring.internal_encoding","UTF-8");

        $template  = $this->setTemplateAddPage($pdf, $font, 'salary_notification.pdf');

        $datas = ClassRegistry::init('SalaryNotification')->findForPDF();

        //提出年月日
        $pdf->SetFont($font, null, 11, true);
        $modified = $this->convertHeiseiDate($datas['SalaryNotification']['hand_in_date']);

        $pdf->SetXY(31.9, 51.4);
        $pdf->Cell(8, 0, $modified['year'], 0, 0, 'C');
        $pdf->SetXY(43.3, 51.4);
        $pdf->Cell(8, 0, $modified['month'], 0, 0, 'C');
        $pdf->SetXY(55, 51.4);
        $pdf->Cell(8, 0, $modified['day'], 0, 0, 'C');

        //税務署
        $pdf->SetFont($font, null, 12.5, true);
        $pdf->SetXY(22, 64);
        $pdf->Cell(30, 0,$datas['User']['tax_office'], 0,0,'R');

        //post_number
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(118, 28);
        $pdf->Cell(40, 0, mb_substr($datas['NameList']['post_number'],0,3), 0);
    $pdf->SetXY(123, 28);
    $pdf->Cell(40, 0, ' - '.mb_substr($datas['NameList']['post_number'],3), 0);

        //address(2)
        $address = $datas['NameList']['prefecture']. $datas['NameList']['city']. $datas['NameList']['address'];
    $pdf->SetFont($font, null, 9, true);
    $len_temp = 27;
    $max_len = 54;
    if(mb_strlen($address) > $max_len){
      $address = mb_substr($address,0,$max_len);
    }
    if(mb_strlen($address) > $len_temp){
      $pdf->SetXY(111.7, 31.6);
      $pdf->Cell(70, 0, mb_substr($address,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(111.7, 34.9);
      $pdf->Cell(70, 0, mb_substr($address,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(111.7, 33);
      $pdf->Cell(70, 0, $address, 0, 0, 'L');
    }

        //phone_number
        $phone = explode('-', $datas['NameList']['phone_number']);
        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(124, 38);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(140, 38);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(157, 38);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        //name_furigana(4)
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(111.7, 42.7);
    $name_furigana = $datas['NameList']['name_furigana'];
    $max_len = 27;
    if(mb_strlen($name_furigana) > $max_len){
      $name_furigana = mb_substr($name_furigana,0,$max_len);
    }

    $name_furigana = mb_convert_kana($name_furigana, "KVC","UTF-8");

    $pdf->Cell(86, 0, $name_furigana, 0);

        //name(5)
    $name = $datas['User']['name'];
    $pdf->SetFont($font, null, 9, true);
    $len_temp = 27;
    $max_len = 54;
    if(mb_strlen($name) > $max_len){
      $name = mb_substr($name,0,$max_len);
    }
    if(mb_strlen($name) > $len_temp){
      $pdf->SetXY(111.7, 48);
      $pdf->Cell(70, 0, mb_substr($name,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(111.7, 52);
      $pdf->Cell(70, 0, mb_substr($name,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(111.7, 50);
      $pdf->Cell(70, 0, $name, 0, 0, 'L');
    }

        //company_number(6)
        $pdf->SetFont($font, null, 9, true);
        $numbers = preg_split("//u", $datas['User']['company_number'], -1, PREG_SPLIT_NO_EMPTY);
        //$numbers = preg_split("//u", '1111111111111', -1, PREG_SPLIT_NO_EMPTY);
        foreach($numbers as $n_key => $numver) {
      //$pdf->SetXY(112.5 + $n_key * 6.5, 61);
      if($n_key <= 4){
        $pdf->SetXY(112 + $n_key * 6.9, 62);
      } else {
        $pdf->SetXY(112 + $n_key * 6.8, 62);
      }
      $pdf->Cell(86, 0, $numver, 0);
        }

        //name_furigana(7)
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(111.7, 66.5);
        $name_furigana = $datas['President']['name_furigana'];
    $max_len = 27;
    if(mb_strlen($name_furigana) > $max_len){
      $name_furigana = mb_substr($name_furigana,0,$max_len);
    }
    $name_furigana = mb_convert_kana($name_furigana, "KVC","UTF-8");

    $pdf->Cell(86, 0, $name_furigana, 0);
        //$pdf->Cell(86, 0, str_repeat('あ', 32), 0);

        //name(8)
    $name = $datas['President']['name'];
        $pdf->SetFont($font, null, 9, true);
    $len_temp = 24;
    $max_len = 48;
    if(mb_strlen($name) > $max_len){
      $name = mb_substr($name,0,$max_len);
    }
    if(mb_strlen($name) > $len_temp){
      $pdf->SetXY(111.7, 72);
      $pdf->Cell(70, 0, mb_substr($name,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(111.7, 76);
      $pdf->Cell(70, 0, mb_substr($name,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(111.7, 73);
      $pdf->Cell(70, 0, $name, 0, 0, 'L');
    }

        //open etc date
        $pdf->SetFont($font, null, 9, true);
    //$open_etc_date = explode('-', $datas['SalaryNotification']['open_etc_date']);
        $open_etc_date = $this->convertHeiseiDate($datas['SalaryNotification']['open_etc_date']);

        $pdf->SetXY(64, 90.5);
        $pdf->Cell(8, 0, $open_etc_date['year'], 0, 0, 'C');
        $pdf->SetXY(79, 90.5);
        $pdf->Cell(8, 0, $open_etc_date['month'], 0, 0, 'C');
        $pdf->SetXY(96, 90.5);
        $pdf->Cell(8, 0, $open_etc_date['day'], 0, 0, 'C');

        //salary_start_date
        //$salary_start_date = explode('-', $datas['SalaryNotification']['salary_start_date']);
    $salary_start_date = $this->convertHeiseiDate($datas['SalaryNotification']['salary_start_date']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(156, 90.5);
        $pdf->Cell(8, 0, $salary_start_date['year'], 0, 0, 'C');
        $pdf->SetXY(170, 90.5);
        $pdf->Cell(8, 0, $salary_start_date['month'], 0, 0, 'C');
        $pdf->SetXY(185, 90.5);
        $pdf->Cell(8, 0, $salary_start_date['day'], 0, 0, 'C');

    //open_class
        $pdf->SetFont($font, null, 12, true);
    if($datas['SalaryNotification']['open_class'] == 1){
      $pdf->SetXY(35.8, 106);
      $pdf->Cell(15, 0, '✓', 0);
    } else if($datas['SalaryNotification']['open_class'] == 2){
      $pdf->SetXY(35.8, 112);
      $pdf->Cell(15, 0, '✓', 0);
    }


    //moving_class
        $pdf->SetFont($font, null, 12, true);
    if($datas['SalaryNotification']['moving_class'] == 1){
      $pdf->SetXY(35.8, 122);
      $pdf->Cell(15, 0, '✓', 0);
    } else if($datas['SalaryNotification']['moving_class'] == 2){
      $pdf->SetXY(35.8, 129);
      $pdf->Cell(15, 0, '✓', 0);
    }


    //moving_reason
        $pdf->SetFont($font, null, 12, true);
    if($datas['SalaryNotification']['moving_reason'] == 1){
      $pdf->SetXY(44.1, 134.5);
      $pdf->Cell(15, 0, '✓', 0);
    } else if($datas['SalaryNotification']['moving_reason'] == 2){
      $pdf->SetXY(63.8, 134.5);
      $pdf->Cell(15, 0, '✓', 0);
    } else if($datas['SalaryNotification']['moving_reason'] == 3){
      $pdf->SetXY(83.8, 134.5);
      $pdf->Cell(15, 0, '✓', 0);
    } else if($datas['SalaryNotification']['moving_reason'] == 4){
      $pdf->SetXY(44.1, 138.5);
      $pdf->Cell(15, 0, '✓', 0);
    }

    //moving_other_reason(13)
    $pdf->SetFont($font, null, 9, true);
    $moving_other_reason = $datas['SalaryNotification']['moving_other_reason'];
    $max_len = 13;
    if(mb_strlen($moving_other_reason) > $max_len){
      $moving_other_reason = mb_substr($moving_other_reason,0,$max_len);
    }
    $pdf->SetXY(50, 143.8);
    $pdf->Cell(40, 0, $moving_other_reason, 0, 0, 'C');

    //close_class
        $pdf->SetFont($font, null, 12, true);
    if($datas['SalaryNotification']['close_class'] == 1){
      $pdf->SetXY(36, 149.5);
      $pdf->Cell(15, 0, '✓', 0);
    } else if($datas['SalaryNotification']['close_class'] == 2){
      $pdf->SetXY(70.5, 149.5);
      $pdf->Cell(15, 0, '✓', 0);
    }

    //other_reason(15)
    $pdf->SetFont($font, null, 9, true);
    $other_reason = $datas['SalaryNotification']['other_reason'];
    $max_len = 20;
    if(mb_strlen($other_reason) > $max_len){
      $other_reason = mb_substr($other_reason,0,$max_len);
    }
    $pdf->SetXY(53, 158.9);
    $pdf->Cell(40, 0, $other_reason, 0, 0, 'C');

    //previous_name_furigana(16)
    $pdf->SetFont($font, null, 8.5, true);
    $pdf->SetXY(47.3, 182.2);
    $previous_name_furigana = $datas['SalaryNotification']['previous_name_furigana'];
    $max_len = 24;
    if(mb_strlen($previous_name_furigana) > $max_len){
      $previous_name_furigana = mb_substr($previous_name_furigana,0,$max_len);
    }

    $previous_name_furigana = mb_convert_kana($previous_name_furigana, "KVC","UTF-8");

    $pdf->Cell(86, 0, $previous_name_furigana, 0);

    //previous_name(17)
    $pdf->SetFont($font, null, 8.8, true);
    $previous_name = $datas['SalaryNotification']['previous_name'];
    $len_temp = 23;
    $max_len = 46;
    if(mb_strlen($previous_name) > $max_len){
      $previous_name = mb_substr($previous_name,0,$max_len);
    }
    if(mb_strlen($previous_name) > $len_temp){
      $pdf->SetXY(47.3, 188);
      $pdf->Cell(70, 0, mb_substr($previous_name,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(47.3, 192.5);
      $pdf->Cell(70, 0, mb_substr($previous_name,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(47.3, 190);
      $pdf->Cell(70, 0, $previous_name, 0, 0, 'L');
    }

    //previous_post_number
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(52, 197.5);
    $pdf->Cell(86, 0, mb_substr($datas['SalaryNotification']['previous_post_number'],0,3), 0);
    $pdf->SetXY(57, 197.5);
    $pdf->Cell(86, 0, ' - '.mb_substr($datas['SalaryNotification']['previous_post_number'],3), 0);

    //previous_address(19)
    $previous_address = $datas['SalaryNotification']['previous_prefecture']. $datas['SalaryNotification']['previous_city']. $datas['SalaryNotification']['previous_address'];
    $pdf->SetFont($font, null, 8.5, true);
    $len_temp = 24;
    $max_len = 48;
    if(mb_strlen($previous_address) > $max_len){
      $previous_address = mb_substr($previous_address,0,$max_len);
    }
    if(mb_strlen($previous_address) > $len_temp){
      $pdf->SetXY(47.3, 202.5);
      $pdf->Cell(70, 0, mb_substr($previous_address,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(47.3, 206.5);
      $pdf->Cell(70, 0, mb_substr($previous_address,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(47.3, 204);
      $pdf->Cell(70, 0, $previous_address, 0, 0, 'L');
    }

    //previous_phone_number
    $pdf->SetFont($font, null, 10, true);
        $phone = explode('-', $datas['SalaryNotification']['previous_phone_number']);
        $pdf->SetXY(62, 210.5);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(79, 210.5);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(97, 210.5);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

    //previous_manager_name_furigana(21)
    $pdf->SetFont($font, null, 8.5, true);
    $pdf->SetXY(47.3, 215.5);
    $previous_manager_name_furigana = $datas['SalaryNotification']['previous_manager_name_furigana'];
    $max_len = 24;
    if(mb_strlen($previous_manager_name_furigana) > $max_len){
      $previous_manager_name_furigana = mb_substr($previous_manager_name_furigana,0,$max_len);
    }
    $previous_manager_name_furigana = mb_convert_kana($previous_manager_name_furigana, "KVC","UTF-8");

    $pdf->Cell(86, 0, $previous_manager_name_furigana, 0);

    //previous_manager_name(22)
    $pdf->SetFont($font, null, 8.5, true);
    $previous_manager_name = $datas['SalaryNotification']['previous_manager_name'];
    $len_temp = 24;
    $max_len = 48;
    if(mb_strlen($previous_manager_name) > $max_len){
      $previous_manager_name = mb_substr($previous_manager_name,0,$max_len);
    }
    if(mb_strlen($previous_manager_name) > $len_temp){
      $pdf->SetXY(47.3, 221);
      $pdf->Cell(70, 0, mb_substr($previous_manager_name,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(47.3, 225.5);
      $pdf->Cell(70, 0, mb_substr($previous_manager_name,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(47.3, 223.5);
      $pdf->Cell(70, 0, $previous_manager_name, 0, 0, 'L');
    }

    //new_name_furigana(23)
    $pdf->SetFont($font, null, 8.5, true);
    $pdf->SetXY(123.5, 182.2);
    $new_name_furigana = $datas['SalaryNotification']['new_name_furigana'];
    $max_len = 24;
    if(mb_strlen($new_name_furigana) > $max_len){
      $new_name_furigana = mb_substr($new_name_furigana,0,$max_len);
    }

    $new_name_furigana = mb_convert_kana($new_name_furigana, "KVC","UTF-8");

    $pdf->Cell(86, 0, $new_name_furigana, 0);

    //new_name(24)
    $pdf->SetFont($font, null, 8.5, true);
    $new_name = $datas['SalaryNotification']['new_name'];
    $len_temp = 24;
    $max_len = 48;
    if(mb_strlen($new_name) > $max_len){
      $new_name = mb_substr($new_name,0,$max_len);
    }
    if(mb_strlen($new_name) > $len_temp){
      $pdf->SetXY(123.5, 188);
      $pdf->Cell(70, 0, mb_substr($new_name,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(123.5, 192.5);
      $pdf->Cell(70, 0, mb_substr($new_name,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(123.5, 190);
      $pdf->Cell(70, 0, $new_name, 0, 0, 'L');
    }

    //new_post_number
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(129, 197.5);
    //$pdf->Cell(86, 0, $datas['SalaryNotification']['new_post_number'], 0);
    $pdf->Cell(86, 0, mb_substr($datas['SalaryNotification']['new_post_number'],0,3), 0);
    $pdf->SetXY(134, 197.5);
    $pdf->Cell(86, 0, ' - '.mb_substr($datas['SalaryNotification']['new_post_number'],3), 0);

    //new_address(26)
    $new_address = $datas['SalaryNotification']['new_prefecture']. $datas['SalaryNotification']['new_city']. $datas['SalaryNotification']['new_address'];
    $pdf->SetFont($font, null, 8.5, true);
    $len_temp = 24;
    $max_len = 48;
    if(mb_strlen($new_address) > $max_len){
      $new_address = mb_substr($new_address,0,$max_len);
    }
    if(mb_strlen($new_address) > $len_temp){
      $pdf->SetXY(123.5, 202.5);
      $pdf->Cell(70, 0, mb_substr($new_address,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(123.5, 206.5);
      $pdf->Cell(70, 0, mb_substr($new_address,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(123.5, 204);
      $pdf->Cell(70, 0, $new_address, 0, 0, 'L');
    }

    //new_phone_number
    $pdf->SetFont($font, null, 10, true);
        $phone = explode('-', $datas['SalaryNotification']['new_phone_number']);
        $pdf->SetXY(136, 210.5);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(154, 210.5);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(174, 210.5);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

    //new_manager_name_furigana(28)
    $pdf->SetFont($font, null, 8, true);
    $pdf->SetXY(123.5, 215.5);
    $new_manager_name_furigana = $datas['SalaryNotification']['new_manager_name_furigana'];
    $max_len = 25;
    if(mb_strlen($new_manager_name_furigana) > $max_len){
      $new_manager_name_furigana = mb_substr($new_manager_name_furigana,0,$max_len);
    }

    $new_manager_name_furigana = mb_convert_kana($new_manager_name_furigana, "KVC","UTF-8");

    $pdf->Cell(86, 0, $new_manager_name_furigana, 0);

    //new_manager_name(29)
    $pdf->SetFont($font, null, 8.5, true);
    $new_manager_name = $datas['SalaryNotification']['new_manager_name'];
    $len_temp = 24;
    $max_len = 48;
    if(mb_strlen($new_manager_name) > $max_len){
      $new_manager_name = mb_substr($new_manager_name,0,$max_len);
    }
    if(mb_strlen($new_manager_name) > $len_temp){
      $pdf->SetXY(123.5, 221);
      $pdf->Cell(70, 0, mb_substr($new_manager_name,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(123.5, 225.5);
      $pdf->Cell(70, 0, mb_substr($new_manager_name,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(123.5, 223.5);
      $pdf->Cell(70, 0, $new_manager_name, 0, 0, 'L');
    }

    //board_member_num(30)
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(43.3, 231.8);
    if($datas['SalaryNotification']['board_member_num'] > 0){
      $pdf->Cell(15, 0, number_format($datas['SalaryNotification']['board_member_num']), 0, 0, 'R');
    }

    //employee_num(31)
    $pdf->SetFont($font, null,9, true);
    $pdf->SetXY(71.5, 231.8);
    if($datas['SalaryNotification']['employee_num'] > 0){
      $pdf->Cell(15, 0, number_format($datas['SalaryNotification']['employee_num']), 0, 0, 'R');
    }

    //other_employee(32)
    $pdf->SetFont($font, null, 8.5, true);
    $pdf->SetXY(91.5, 231.7);
    $text_temp = $datas['SalaryNotification']['other_employee'];
    $max_len = 3;
    if(mb_strlen($text_temp) > $max_len){
      $text_temp = mb_substr($text_temp,0,$max_len);
    }
    $pdf->Cell(15, 0, $text_temp, 0, 0, 'C');

    //other_employee_num(33)
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(99.6, 231.8);
    if($datas['SalaryNotification']['other_employee_num2'] > 0){
      $pdf->Cell(15, 0, number_format($datas['SalaryNotification']['other_employee_num2']), 0, 0, 'R');
    }

    //other_employee2(34)
    $pdf->SetFont($font, null, 8.5, true);
    $pdf->SetXY(118.2, 231.7);
    $text_temp = $datas['SalaryNotification']['other_employee2'];
    $max_len = 3;
    if(mb_strlen($text_temp) > $max_len){
      $text_temp = mb_substr($text_temp,0,$max_len);
    }
    $pdf->Cell(15, 0, $text_temp, 0, 0, 'C');

    //other_employee_num2(35)
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(126.3, 231.8);
    if($datas['SalaryNotification']['other_employee_num2'] > 0){
      $pdf->Cell(15, 0, number_format($datas['SalaryNotification']['other_employee_num2']), 0, 0, 'R');
    }

    //other_employee3(36)
    $pdf->SetFont($font, null, 8.5, true);
    $pdf->SetXY(146.8, 231.7);
    $text_temp = $datas['SalaryNotification']['other_employee3'];
    $max_len = 3;
    if(mb_strlen($text_temp) > $max_len){
      $text_temp = mb_substr($text_temp,0,$max_len);
    }
    $pdf->Cell(15, 0, $text_temp, 0, 0, 'C');

    //other_employee_num3(37)
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(155.3, 231.8);
    if($datas['SalaryNotification']['other_employee_num4'] > 0){
      $pdf->Cell(15, 0, number_format($datas['SalaryNotification']['other_employee_num4']), 0, 0, 'R');
    }

    //employee_sum(38)
    $pdf->SetFont($font, null, 9, true);
    $pdf->SetXY(179.8, 231.8);
    $pdf->Cell(15, 0, number_format($datas['SalaryNotification']['employee_sum']), 0, 0, 'R');

    //other_reference_matters
    $other_reference_matters = $datas['SalaryNotification']['other_reference_matters'];
    $pdf->SetFont($font, null, 8.5, true);
    $len_temp = 59;
    $max_len = 116;
    if(mb_strlen($other_reference_matters) > $max_len){
      $other_reference_matters = mb_substr($other_reference_matters,0,$max_len);
    }
    if(mb_strlen($other_reference_matters) > $len_temp){
      $pdf->SetXY(20.3, 241);
      $pdf->Cell(86, 0, mb_substr($other_reference_matters,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(20.3, 244.5);
      $pdf->Cell(86, 0, mb_substr($other_reference_matters,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(20.3, 243);
      $pdf->Cell(86, 0, $other_reference_matters, 0, 0, 'L');
    }

        return $pdf;
    }

    /**
     *
     * @param FPDI OBJ $pdf
     * @param TCPDF_FONTS $font
     * @return FPDI OBJ $pdf
     */
    function export_salary_payment_deadlines($pdf, $font) {
    ini_set("mbstring.internal_encoding","UTF-8");

        $template  = $this->setTemplateAddPage($pdf, $font, 'salary_payment_deadline.pdf');

        $datas = ClassRegistry::init('SalaryPaymentDeadline')->findForPaymentDeadlinePDF();

        //提出年月日
        $pdf->SetFont($font, null, 11, true);
        $modified = $this->convertHeiseiDate($datas['SalaryPaymentDeadline']['hand_in_date']);

        $pdf->SetXY(42.5, 50);
        $pdf->Cell(8, 0, $modified['year'], 0, 0, 'C');
        $pdf->SetXY(54, 50);
        $pdf->Cell(8, 0, $modified['month'], 0, 0, 'C');
        $pdf->SetXY(65.7, 50);
        $pdf->Cell(8, 0, $modified['day'], 0, 0, 'C');

        //税務署
        $pdf->SetFont($font, null, 12.5, true);
        $pdf->SetXY(39.4, 75.4);
        $pdf->Cell(30, 0,$datas['User']['tax_office'], 0,0,'R');

        //post_number
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(133, 26.5);
        $pdf->Cell(40, 0, mb_substr($datas['User']['NameList']['post_number'],0,3), 0);
    $pdf->SetXY(139, 26.5);
    $pdf->Cell(40, 0, ' - '.mb_substr($datas['User']['NameList']['post_number'],3), 0);

        //address
        $address = $datas['User']['NameList']['prefecture']. $datas['User']['NameList']['city']. $datas['User']['NameList']['address'];
    $pdf->SetFont($font, null, 8.5, true);
    $len_temp = 21;
    $max_len = 42;
    if(mb_strlen($address) > $max_len){
      $address = mb_substr($address,0,$max_len);
    }
    if(mb_strlen($address) > $len_temp){
      $pdf->SetXY(127, 31);
      $pdf->Cell(50, 0, mb_substr($address,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(127, 35);
      $pdf->Cell(50, 0, mb_substr($address,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(127, 32.5);
      $pdf->Cell(50, 0, $address, 0, 0, 'L');
    }

        //phone_number
        $phone = explode('-', $datas['User']['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(146, 39.5);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(163, 39.5);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(180, 39.5);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        //NameList name_furigana(4)
        $pdf->SetFont($font, null, 8.5, true);
        $pdf->SetXY(127, 44.5);
        $name_furigana = $datas['User']['NameList']['name_furigana'];
    $max_len = 21;
    if(mb_strlen($name_furigana) > $max_len){
      $name_furigana = mb_substr($name_furigana,0,$max_len);
    }
    $name_furigana = mb_convert_kana($name_furigana, "KVC","UTF-8");

    $pdf->Cell(50, 0, $name_furigana, 0);

        //name(5)
        $pdf->SetFont($font, null, 8.5, true);
    $name = $datas['User']['name'];
    $len_temp = 21;
    $max_len = 42;
    if(mb_strlen($name) > $max_len){
      $name = mb_substr($name,0,$max_len);
    }
    if(mb_strlen($name) > $len_temp){
      $pdf->SetXY(127, 50);
      $pdf->Cell(50, 0, mb_substr($name,0,$len_temp), 0);
      $pdf->SetXY(127, 54);
      $pdf->Cell(50, 0, mb_substr($name,$len_temp), 0);
    } else {
      $pdf->SetXY(127, 52);
      $pdf->Cell(50, 0, $name, 0);
    }

        //company_number
        $pdf->SetFont($font, null, 9, true);
        $numbers = preg_split("//u", $datas['User']['company_number'], -1, PREG_SPLIT_NO_EMPTY);
        //$numbers = preg_split("//u", '1111111111111', -1, PREG_SPLIT_NO_EMPTY);
        foreach($numbers as $n_key => $numver) {
      $pdf->SetXY(127 + $n_key * 5, 65);
      $pdf->Cell(8, 0, $numver, 0);
        }

        //President name_furigana(7)
    if(!empty($datas['President']['name_furigana'])){
      $pdf->SetFont($font, null, 8.5, true);
      $pdf->SetXY(127, 69.8);
      $name_furigana = $datas['President']['name_furigana'];
      $max_len = 21;
      if(mb_strlen($name_furigana) > $max_len){
        $name_furigana = mb_substr($name_furigana,0,$max_len);
      }

      $name_furigana = mb_convert_kana($name_furigana, "KVC","UTF-8");

      $pdf->Cell(86, 0, $name_furigana, 0);
    }

        //President name(8)
    if(!empty($datas['President']['name'])){
      $pdf->SetFont($font, null, 8.5, true);
      $name = $datas['President']['name'];
      $len_temp = 18;
      $max_len = 36;
      if(mb_strlen($name) > $max_len){
        $name = mb_substr($name,0,$max_len);
      }
      if(mb_strlen($name) > $len_temp){
        $pdf->SetXY(127, 76);
        $pdf->Cell(50, 0, mb_substr($name,0,$len_temp), 0, 0, 'L');
        $pdf->SetXY(127, 80);
        $pdf->Cell(50, 0, mb_substr($name,$len_temp), 0, 0, 'L');
      } else {
        $pdf->SetXY(127, 79);
        $pdf->Cell(50, 0, $name, 0, 0, 'L');
      }
    }

    //notoku_post_number
    if(!empty($datas['SalaryPaymentDeadline']['notoku_post_number'])){
      $pdf->SetFont($font, null, 9.3, true);
      $pdf->SetXY(105, 102.5);
      $notoku_post_number = $datas['SalaryPaymentDeadline']['notoku_post_number'];
      $max_len = 8;
      if(mb_strlen($notoku_post_number) > $max_len){
        $notoku_post_number = mb_substr($notoku_post_number,0,$max_len);
      }
      $pdf->Cell(40, 0, mb_substr($notoku_post_number,0,3), 0);
      $pdf->SetXY(110, 102.5);
      $pdf->Cell(40, 0, ' - '.mb_substr($notoku_post_number,3), 0);
    }

    //notoku_prefecture, notoku_city, notoku_address
    $address = $datas['SalaryPaymentDeadline']['notoku_prefecture'] . $datas['SalaryPaymentDeadline']['notoku_city'] . $datas['SalaryPaymentDeadline']['notoku_address'];
    $pdf->SetFont($font, null, 9, true);
    $len_temp = 28;
    $max_len = 56;
    if(mb_strlen($address) > $max_len){
      $address = mb_substr($address,0,$max_len);
    }
    if(mb_strlen($address) > $len_temp){
      $pdf->SetXY(99.3, 108);
      $pdf->Cell(70, 0, mb_substr($address,0,$len_temp), 0, 0, 'L');
      $pdf->SetXY(99.3, 113);
      $pdf->Cell(70, 0, mb_substr($address,$len_temp), 0, 0, 'L');
    } else {
      $pdf->SetXY(99.3, 111);
      $pdf->Cell(70, 0, $address, 0, 0, 'L');
    }

        //notoku_phone_number
        $notoku_phone_number = explode('-', $datas['SalaryPaymentDeadline']['notoku_phone_number']);
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(117, 120);
        $pdf->Cell(8, 0, $notoku_phone_number[0], 0, 0, 'C');
        $pdf->SetXY(135, 120);
        $pdf->Cell(8, 0, $notoku_phone_number[1], 0, 0, 'C');
        $pdf->SetXY(153, 120);
        $pdf->Cell(8, 0, $notoku_phone_number[2], 0, 0, 'C');

        //pay_date
        $pay_date = explode('-', $datas['SalaryPaymentDeadline']['pay_date']);
        if($datas['SalaryPaymentDeadline']['pay_date'] && $pay_date[0] != '0000'){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(98, 141);
      /*
          $pdf->Cell(86, 0, $pay_date[0], 0);
          $pdf->SetXY(110, 141);
          $pdf->Cell(86, 0, $pay_date[1], 0);
      */
          $pdf->Cell(8, 0, $pay_date[0], 0, 0, "C");
          $pdf->SetXY(108.5, 141);
          $pdf->Cell(8, 0, intval($pay_date[1]), 0, 0, "C");
        }

        //pay_num_temporary
        $pdf->SetFont($font, null, 9.3, true);
    /*
        $pdf->SetXY(129, 133.8);
        $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary']), 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_num_temporary']){
          $pdf->SetXY(68.2, 133.8);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary']), 0, 0, 'R');
        }

        //pay_num
        if($datas['SalaryPaymentDeadline']['pay_num']){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(63.5, 140.8);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num']), 0, 0, 'R');
        }

        //pay_sum_temporary
        if($datas['SalaryPaymentDeadline']['pay_sum_temporary']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 133.8);
          $pdf->SetXY(105, 133.6);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum_temporary'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum_temporary']), 0, 0, 'R');
        }

        //pay_sum
        if($datas['SalaryPaymentDeadline']['pay_sum']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 141);
          $pdf->SetXY(100, 140.8);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum']), 0, 0, 'R');
        }


        //pay_date2
        $pay_date2 = explode('-', $datas['SalaryPaymentDeadline']['pay_date2']);
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(98, 154);
    /*
        $pdf->Cell(86, 0, $pay_date2[0], 0);
        $pdf->SetXY(110, 154.2);
        $pdf->Cell(86, 0, $pay_date2[1], 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_date2'] && $pay_date2[0] != '0000'){
          $pdf->Cell(8, 0, $pay_date2[0], 0, 0, "C");
          $pdf->SetXY(108.5, 154);
          $pdf->Cell(8, 0, intval($pay_date2[1]), 0, 0, "C");
        }

        //pay_num_temporary2
        $pdf->SetFont($font, null, 9.3, true);
    /*
        $pdf->SetXY(129, 147);
        $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary2']), 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_num_temporary2']){
          $pdf->SetXY(68.2, 147);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary2']), 0, 0, 'R');
        }

        //pay_num2
        if($datas['SalaryPaymentDeadline']['pay_num2']){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(63.5, 154.2);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num2']), 0, 0, 'R');
        }

        //pay_sum_temporary2
        if($datas['SalaryPaymentDeadline']['pay_sum_temporary2']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 147);
          $pdf->SetXY(105, 146.8);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum_temporary2'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum_temporary2']), 0, 0, 'R');
        }

        //pay_sum2
        if($datas['SalaryPaymentDeadline']['pay_sum2']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 154.2);
          $pdf->SetXY(100, 154);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum2'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum2']), 0, 0, 'R');
        }

        //pay_date3

        $pay_date3 = explode('-', $datas['SalaryPaymentDeadline']['pay_date3']);
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(98, 167.2);
    /*
        $pdf->Cell(86, 0, $pay_date3[0], 0);
        $pdf->SetXY(110, 167.4);
        $pdf->Cell(86, 0, $pay_date3[1], 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_date3'] && $pay_date3[0] != '0000'){
          $pdf->Cell(8, 0, $pay_date3[0], 0, 0, "C");
          $pdf->SetXY(108.5, 167.2);
          $pdf->Cell(8, 0, intval($pay_date3[1]), 0, 0, "C");
        }

        //pay_num_temporary3
        $pdf->SetFont($font, null, 9.3, true);
    /*
        $pdf->SetXY(129, 160.2);
        $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary3']), 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_num_temporary3']){
          $pdf->SetXY(68.2, 160.2);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary3']), 0, 0, 'R');
        }

        //pay_num3
        if($datas['SalaryPaymentDeadline']['pay_num3']){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(63.5, 167.4);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num3']), 0, 0, 'R');
        }

        //pay_sum_temporary3
        if($datas['SalaryPaymentDeadline']['pay_sum_temporary3']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 160.2);
          $pdf->SetXY(105, 160);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum_temporary3'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum_temporary3']), 0, 0, 'R');
        }

        //pay_sum3
        if($datas['SalaryPaymentDeadline']['pay_sum3']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 167.4);
          $pdf->SetXY(100, 167.2);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum3'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum3']), 0, 0, 'R');
        }

        //pay_date4
        $pay_date4 = explode('-', $datas['SalaryPaymentDeadline']['pay_date4']);
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(98, 180.4);
    /*
        $pdf->Cell(86, 0, $pay_date4[0], 0);
        $pdf->SetXY(110, 180.6);
        $pdf->Cell(86, 0, $pay_date4[1], 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_date4'] && $pay_date4[0] != '0000'){
          $pdf->Cell(8, 0, $pay_date4[0], 0, 0, "C");
          $pdf->SetXY(108.5, 180.4);
          $pdf->Cell(8, 0, intval($pay_date4[1]), 0, 0, "C");
        }

        //pay_num_temporary4
        $pdf->SetFont($font, null, 9.3, true);
    /*
        $pdf->SetXY(129, 173.4);
        $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary4']), 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_num_temporary4']){
          $pdf->SetXY(68.2, 173.4);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary4']), 0, 0, 'R');
        }

        //pay_num4
        if($datas['SalaryPaymentDeadline']['pay_num4']){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(63.5, 180.6);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num4']), 0, 0, 'R');
        }

        //pay_sum_temporary4
        if($datas['SalaryPaymentDeadline']['pay_sum_temporary4']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 173.4);
          $pdf->SetXY(105, 173.2);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum_temporary4'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum_temporary4']), 0, 0, 'R');
        }

        //pay_sum4
        if($datas['SalaryPaymentDeadline']['pay_sum4']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 180.6);
          $pdf->SetXY(100, 180.4);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum4'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum4']), 0, 0, 'R');
        }

        //pay_date5
        $pay_date5 = explode('-', $datas['SalaryPaymentDeadline']['pay_date5']);
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(98, 193.6);
    /*
        $pdf->Cell(86, 0, $pay_date5[0], 0);
        $pdf->SetXY(110, 193.8);
        $pdf->Cell(86, 0, $pay_date5[1], 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_date5'] && $pay_date5[0] != '0000'){
          $pdf->Cell(8, 0, $pay_date5[0], 0, 0, "C");
          $pdf->SetXY(108.5, 193.6);
          $pdf->Cell(8, 0, intval($pay_date5[1]), 0, 0, "C");
        }
        //pay_num_temporary5
        $pdf->SetFont($font, null, 9.3, true);
    /*
        $pdf->SetXY(129, 186.6);
        $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary5']), 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_num_temporary5']){
          $pdf->SetXY(68.2, 186.6);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary5']), 0, 0, 'R');
        }

        //pay_num5
        if($datas['SalaryPaymentDeadline']['pay_num5']){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(63.5, 193.8);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num5']), 0, 0, 'R');
        }

        //pay_sum_temporary5
        if($datas['SalaryPaymentDeadline']['pay_sum_temporary5']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 186.6);
          $pdf->SetXY(105, 186.4);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum_temporary5'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum_temporary5']), 0, 0, 'R');
        }

        //pay_sum5
        if($datas['SalaryPaymentDeadline']['pay_sum5']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 193.8);
          $pdf->SetXY(100, 193.6);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum5'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum5']), 0, 0, 'R');
        }

        //pay_date6
        $pay_date6 = explode('-', $datas['SalaryPaymentDeadline']['pay_date6']);
        $pdf->SetFont($font, null, 9.3, true);
        $pdf->SetXY(98, 206.8);
    /*
        $pdf->Cell(86, 0, $pay_date6[0], 0);
        $pdf->SetXY(110, 207);
        $pdf->Cell(86, 0, $pay_date6[1], 0);
    */
        if($datas['SalaryPaymentDeadline']['pay_date6'] && $pay_date6[0] != '0000'){
          $pdf->Cell(8, 0, $pay_date6[0], 0, 0, "C");
          $pdf->SetXY(108.5, 206.8);
          $pdf->Cell(8, 0, intval($pay_date6[1]), 0, 0, "C");
        }
        //pay_num_temporary6
        if($datas['SalaryPaymentDeadline']['pay_num_temporary6']){
          $pdf->SetFont($font, null, 9.3, true);
      /*
          $pdf->SetXY(129, 199.8);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary6']), 0);
      */
          $pdf->SetXY(68.2, 199.8);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num_temporary6']), 0, 0, 'R');
        }

        //pay_num6
        if($datas['SalaryPaymentDeadline']['pay_num6']){
          $pdf->SetFont($font, null, 9.3, true);
          $pdf->SetXY(63.5, 207);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_num6']), 0, 0, 'R');
        }

        //pay_sum_temporary6
        if($datas['SalaryPaymentDeadline']['pay_sum_temporary6']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 199.8);
          $pdf->SetXY(105, 199.6);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum_temporary6'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum_temporary6']), 0, 0, 'R');
        }

        //pay_sum6
        if($datas['SalaryPaymentDeadline']['pay_sum6']){
          $pdf->SetFont($font, null, 9.3, true);
          //$pdf->SetXY(163, 207);
          $pdf->SetXY(100, 206.8);
          //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['pay_sum6'], 0);
          $pdf->Cell(86, 0, number_format($datas['SalaryPaymentDeadline']['pay_sum6']), 0, 0, 'R');
        }

    //not_pay_reason
    $not_pay_reason = $datas['SalaryPaymentDeadline']['not_pay_reason'];
    $max_len = 196;
    if(mb_strlen($not_pay_reason) > $max_len){
      $not_pay_reason = mb_substr($not_pay_reason, 0, $max_len);
    }
    $pdf->SetFont($font, null, 9, true);
    if(!empty($not_pay_reason)){
      $num_char_per_row = 28;
      $num_row = ceil(mb_strlen($not_pay_reason)/$num_char_per_row);
      $not_pay_reason_temp = $not_pay_reason;
      for($i=0; $i<$num_row; $i++){
        if(mb_strlen($not_pay_reason_temp) > $num_char_per_row){
          $text_temp = mb_substr($not_pay_reason, $i*$num_char_per_row, $num_char_per_row);
          $not_pay_reason_temp = mb_substr($not_pay_reason, ($i+1)*$num_char_per_row);
        } else {
          $text_temp = mb_substr($not_pay_reason, $i*$num_char_per_row);
        }
        $pdf->SetXY(99.3, 214+$i*5);
        $pdf->Cell(70, 0, $text_temp, 0, 0, 'L');
      }
    }

        //cancel_date
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(105, 249);
        //$pdf->Cell(86, 0, $datas['SalaryPaymentDeadline']['cancel_date'], 0, 0, 'C');
        $cancel_date = explode("-", $datas['SalaryPaymentDeadline']['cancel_date']);
        if($cancel_date[0] > 1){
          $str_cancel_date = $cancel_date[0]." 年 ".intval($cancel_date[1])." 月 ".intval($cancel_date[2])." 日";
        }
    /*
    $cancel_date = $this->convertHeiseiDate($datas['SalaryPaymentDeadline']['cancel_date']);
    $str_cancel_date = $cancel_date["year"]." 年 ".intval($cancel_date["month"])." 月 ".intval($cancel_date["day"])." 日";
    */
    $pdf->Cell(86, 0, $str_cancel_date, 0, 0, 'C');

        return $pdf;
    }


    /*consumption_tax_company_notifications*/
    function export_consumption_tax_company_notifications($pdf, $font) {
        ini_set("mbstring.internal_encoding","UTF-8");

        $template  = $this->setTemplateAddPage($pdf, $font, 'consumption_tax_company_notification.pdf');

        $datas = ClassRegistry::init('ConsumptionTaxCompanyNotification')->findForPDF();

        //modified(1)
        $pdf->SetFont($font, null, 9, true);
        $modified = $this->convertHeiseiDate($datas['ConsumptionTaxCompanyNotification']['hand_in_date']);

        $pdf->SetXY(31, 41.7);
        $pdf->Cell(8, 0, $modified['year'], 0, 0, 'C');
        $pdf->SetXY(41, 41.7);
        $pdf->Cell(8, 0, $modified['month'], 0, 0, 'C');
        $pdf->SetXY(51, 41.7);
        $pdf->Cell(8, 0, $modified['day'], 0, 0, 'C');

        //tax_office(2)
        $pdf->SetFont($font, null, 9, true);
        $tax_office = $datas['User']['tax_office'];

        $pdf->SetXY(31, 132);
        $pdf->Cell(15, 0, $tax_office, 0, 0, 'C');


        //address_furigana(3)
        $address_furigana = $datas['NameList']['prefecture_furigana']. $datas['NameList']['city_furigana']. $datas['NameList']['address_furigana'];
        $address_furigana = mb_convert_kana($address_furigana, "KVC");
        $pdf->SetFont($font, null, 9, true);
        $max_len = 28;
        if(mb_strlen($address_furigana) > $max_len){
            $address_furigana = mb_substr($address_furigana,0,$max_len);
        }
        $pdf->SetXY(94.5, 36.8);
        $pdf->Cell(70, 0, $address_furigana, 0, 0);

        //post_number(4)
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(102, 41.2);
        $pdf->Cell(20, 0, mb_substr($datas['NameList']['post_number'],0,3), 0,'C');
        $pdf->SetXY(108, 41.2);
        $pdf->Cell(20, 0, ' - '.mb_substr($datas['NameList']['post_number'],3), 0,'C');

        //address(5)
        $address = $datas['NameList']['prefecture']. $datas['NameList']['citys']. $datas['NameList']['address'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 28;
        $max_len = 56;
        if(mb_strlen($address) > $max_len){
            $address = mb_substr($address,0,$max_len);
        }
        if(mb_strlen($address) > $len_temp){
            $pdf->SetXY(94.5, 46);
            $pdf->Cell(70, 0, mb_substr($address,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(94.5, 51);
            $pdf->Cell(70, 0, mb_substr($address,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(94.5, 48);
            $pdf->Cell(70, 0, $address, 0, 0, 'L');
        }


        $pdf->SetFont($font, null, 10, true);
        $pdf->SetXY(101, 72);
        $pdf->Cell(70, 0, '同　上', 0, 0, 'C');

        //phone_number(6)
        $phone = explode('-', $datas['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(143.5, 55.5);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(156.5, 55.5);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(169, 55.5);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        //name_furigana(11)
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(94.5, 83.8);
        $name_furigana = $datas['NameList']['name_furigana'];
        $name_furigana = mb_convert_kana($name_furigana, "KVC");
        $max_len = 28;
        if(mb_strlen($name_furigana) > $max_len){
            $name_furigana = mb_substr($name_furigana,0,$max_len);
        }
        $pdf->Cell(86, 0, $name_furigana, 0);

        //name(12)
        $name = $datas['User']['name'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 28;
        $max_len = 56;
        if(mb_strlen($name) > $max_len){
            $name = mb_substr($name,0,$max_len);
        }
        if(mb_strlen($name) > $len_temp){
            $pdf->SetXY(94.5, 88.6);
            $pdf->Cell(70, 0, mb_substr($name,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(94.5, 92.7);
            $pdf->Cell(70, 0, mb_substr($name,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(96, 90.5);
            $pdf->Cell(70, 0, $name, 0, 0, 'L');
        }

        //company_number(13)
        $pdf->SetFont($font, null, 9, true);
        //$datas['User']['company_number'] = 456789987654;
        $numbers = preg_split("//u", $datas['User']['company_number'], -1, PREG_SPLIT_NO_EMPTY);
        foreach($numbers as $n_key => $numver) {
            if($n_key <= 2){
                $pdf->SetXY(96 + $n_key * 6.7, 107.5);
            } else if($n_key <= 4){
                $pdf->SetXY(96 + $n_key * 6.9, 107.5);
            } else if($n_key <= 6){
                $pdf->SetXY(96 + $n_key * 7, 107.5);
            } else {
                $pdf->SetXY(96 + $n_key * 7.1, 107.5);
            }
            $pdf->Cell(8, 0, $numver, 0);
        }

        //name_furigana(14)
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(94.5, 112.6);
        $name_furigana = $datas['President']['name_furigana'];
        $name_furigana = mb_convert_kana($name_furigana, "KVC");
        $max_len = 24;
        if(mb_strlen($name_furigana) > $max_len){
            $name_furigana = mb_substr($name_furigana,0,$max_len);
        }
        $pdf->Cell(86, 0, $name_furigana, 0);

        //name(15)
        $name = $datas['President']['name'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 24;
        $max_len = 48;
        if(mb_strlen($name) > $max_len){
            $name = mb_substr($name,0,$max_len);
        }
        if(mb_strlen($name) > $len_temp){
            $pdf->SetXY(94.5, 117.5);
            $pdf->Cell(70, 0, mb_substr($name,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(94.5, 121.5);
            $pdf->Cell(70, 0, mb_substr($name,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(94.5, 119.5);
            $pdf->Cell(70, 0, $name, 0, 0, 'L');
        }

        //President.address_furigana(16)
        $address_furigana = $datas['President']['prefecture_furigana']. $datas['President']['city_furigana']. $datas['President']['address_furigana'];
        $address_furigana = mb_convert_kana($address_furigana, "KVC");
        $pdf->SetFont($font, null, 9, true);
        $max_len = 28;
        if(mb_strlen($address_furigana) > $max_len){
            $address_furigana = mb_substr($address_furigana,0,$max_len);
        }
        $pdf->SetXY(94.5, 127.5);
        $pdf->Cell(70, 0, $address_furigana, 0, 0, 'L');

        //President.address(17)
        $address = $datas['President']['prefecture']. $datas['President']['city']. $datas['President']['address'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 28;
        $max_len = 56;
        if(mb_strlen($address) > $max_len){
            $address = mb_substr($address,0,$max_len);
        }
        if(mb_strlen($address) > $len_temp){
            $pdf->SetXY(94.5, 132);
            $pdf->Cell(70, 0, mb_substr($address,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(94.5, 135.7);
            $pdf->Cell(70, 0, mb_substr($address,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(94.5, 134);
            $pdf->Cell(70, 0, $address, 0, 0, 'L');
        }
        //phone_number(18)
        if(!empty($datas['President']['phone_number'])){
            $phone = explode('-', $datas['President']['phone_number']);
            if(!empty($phone)){
                $pdf->SetFont($font, null, 10, true);
                $pdf->SetXY(139, 139);
                $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
                $pdf->SetXY(154.5, 139);
                $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
                $pdf->SetXY(170.5, 139);
                $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');
            }
        }

        //start_period(19)
        $pdf->SetFont($font, null, 9, true);
        $start_period = $this->convertHeiseiDate($datas['ConsumptionTaxCompanyNotification']['start_period']);

        $pdf->SetXY(79, 160.5);
        $pdf->Cell(8, 0, $start_period['year'], 0, 0, 'C');
        $pdf->SetXY(91, 160.5);
        $pdf->Cell(8, 0, $start_period['month'], 0, 0, 'C');
        $pdf->SetXY(102, 160.5);
        $pdf->Cell(8, 0, $start_period['day'], 0, 0, 'C');

        //end_period(20)
        $end_period = $this->convertHeiseiDate($datas['ConsumptionTaxCompanyNotification']['end_period']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(139, 160.5);
        $pdf->Cell(8, 0, $end_period['year'], 0, 0, 'C');
        $pdf->SetXY(151, 160.5);
        $pdf->Cell(8, 0, $end_period['month'], 0, 0, 'C');
        $pdf->SetXY(162, 160.5);
        $pdf->Cell(8, 0, $end_period['day'], 0, 0, 'C');

        //base_start_period(21)
        $base_start_period = $this->convertHeiseiDate($datas['ConsumptionTaxCompanyNotification']['base_start_period']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(72.5, 171.7);
        $pdf->Cell(8, 0, $base_start_period['year'], 0, 0, 'C');
        $pdf->SetXY(84, 171.7);
        $pdf->Cell(8, 0, $base_start_period['month'], 0, 0, 'C');
        $pdf->SetXY(95.5, 171.7);
        $pdf->Cell(8, 0, $base_start_period['day'], 0, 0, 'C');

        //base_end_period(22)
        $base_end_period = $this->convertHeiseiDate($datas['ConsumptionTaxCompanyNotification']['base_end_period']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(72.5, 183.7);
        $pdf->Cell(8, 0, $base_end_period['year'], 0, 0, 'C');
        $pdf->SetXY(84, 183.7);
        $pdf->Cell(8, 0, $base_end_period['month'], 0, 0, 'C');
        $pdf->SetXY(95.5, 183.7);
        $pdf->Cell(8, 0, $base_end_period['day'], 0, 0, 'C');

        //sales(23)
        $sales = $datas['ConsumptionTaxCompanyNotification']['sales'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(139.5, 170.5);
        $pdf->Cell(40, 0, number_format($sales), 0, 0, 'R');

        //base_sales(24)
        $base_sales = $datas['ConsumptionTaxCompanyNotification']['base_sales'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(139.5, 182.5);
        $pdf->Cell(40, 0, number_format($base_sales), 0, 0, 'R');

        //establishment_date(25)
        $establishment_date = $this->convertHeiseiDate($datas['User']['establishment_date']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(63, 204.1);
        $pdf->Cell(8, 0, $establishment_date['year'], 0, 0, 'C');
        $pdf->SetXY(75.5, 204.1);
        $pdf->Cell(8, 0, $establishment_date['month'], 0, 0, 'C');
        $pdf->SetXY(89, 204.1);
        $pdf->Cell(8, 0, $establishment_date['day'], 0, 0, 'C');

		//draw ellipse
		$year_org = $establishment_date['year'] + 1988;
        $x = 57.7;
        $y = 196.4;
        $width = 4.8;
        $height = 2.8;
		if($year_org > 1988){
			//4平成
			$x = 92.6;
			$width = 5.5;

		} else if($year_org > 1925){
			//3昭和
			$x = 80.7;
			$width = 5.5;

		} else if($year_org > 1911){
			//2大正
			$x = 69;
			$width = 5.4;
		} else {
			//1明治
			$x = 57.7;
			$width = 4.8;
		}
		$pdf->Ellipse($x, $y, $width, $height);

        //start_month(26)
        $start_month = $datas['ConsumptionTaxCompanyNotification']['start_month'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(143, 194.5);
        $pdf->Cell(8, 0, $start_month, 0, 0, 'C');

        //start_day(27)
        $start_day = $datas['ConsumptionTaxCompanyNotification']['start_day'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(151, 194.5);
        $pdf->Cell(8, 0, $start_day, 0, 0, 'C');

        //end_month(28)
        $end_month = $datas['ConsumptionTaxCompanyNotification']['end_month'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(166.5, 194.5);
        $pdf->Cell(8, 0, $end_month, 0, 0, 'C');

        //end_day(29)
        $end_day = $datas['ConsumptionTaxCompanyNotification']['end_day'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(174.5, 194.5);
        $pdf->Cell(8, 0, $end_day, 0, 0, 'C');

        //capital_sum(30)
        $capital_sum = $datas['User']['capital_sum'];
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(139.5, 204);
        $pdf->Cell(40, 0, number_format($capital_sum), 0, 0, 'R');

        //business_content(31)
        $business_content = $datas['ConsumptionTaxCompanyNotification']['business_content'];
        $pdf->SetFont($font, null, 9, true);
        $max_len = 21;
        if(mb_strlen($business_content) > $max_len){
            $business_content = mb_substr($business_content,0,$max_len);
        }
        $pdf->SetXY(54.5, 212.5);
        $pdf->Cell(70, 0, $business_content, 0, 0, 'L');

        //notification_class(32)
        $notification_class = $datas['ConsumptionTaxCompanyNotification']['notification_class'];
        $x = 149.5;
        $y = 214.5;
        $width = 4.4;
        $height = 2.7;
		if($notification_class == 1){
			$x = 149.5;
			$width = 4.4;
		} else if($notification_class == 2){
			$x = 158.1;
			$width = 4.4;
		} else if($notification_class == 3){
			$x = 168.2;
			$width = 5.4;
		} else if($notification_class == 4){
			$x = 179.8;
			$width = 5.7;
		}
		$pdf->Ellipse($x, $y, $width, $height);

        //note(33)
        $note = $datas['ConsumptionTaxCompanyNotification']['note'];
        $max_len = 60;
        if(mb_strlen($note) > $max_len){
            $note = mb_substr($note, 0, $max_len);
        }
        $pdf->SetFont($font, null, 9, true);
        if(!empty($note)){
            $num_char_per_row = 20;
            $num_row = 3;
            $note_temp = $note;
            for($i=0; $i<$num_row; $i++){
                if(mb_strlen($note_temp) > $num_char_per_row){
                    $text_temp = mb_substr($note, $i*$num_char_per_row, $num_char_per_row);
                    $note_temp = mb_substr($note, ($i+1)*$num_char_per_row);
                } else {
                    $text_temp = mb_substr($note, $i*$num_char_per_row);
                }
                $pdf->SetXY(43, 220+$i*5);
                $pdf->Cell(70, 0, $text_temp, 0, 0, 'L');
            }
        }

        return $pdf;
    }

    /*consumption_tax_easy_notification*/
    function export_consumption_tax_easy_notifications($pdf, $font) {
        ini_set("mbstring.internal_encoding","UTF-8");

        $template  = $this->setTemplateAddPage($pdf, $font, 'consumption_tax_easy_notification.pdf');

        $datas = ClassRegistry::init('ConsumptionTaxEasyNotification')->findForPDF();

        //modified(1)
        $pdf->SetFont($font, null, 9, true);
        $modified = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['hand_in_date']);

        $pdf->SetXY(29.5, 28.4);
        $pdf->Cell(8, 0, $modified['year'], 0, 0, 'C');
        $pdf->SetXY(37.5, 28.4);
        $pdf->Cell(8, 0, $modified['month'], 0, 0, 'C');
        $pdf->SetXY(46, 28.4);
        $pdf->Cell(8, 0, $modified['day'], 0, 0, 'C');

        //tax_office(2)
        $pdf->SetFont($font, null, 9, true);
        $tax_office = $datas['User']['tax_office'];

        $pdf->SetXY(25, 76.2);
        $pdf->Cell(15, 0, $tax_office, 0, 0, 'C');


        //address_furigana(3)
        $address_furigana = $datas['NameList']['prefecture_furigana']. $datas['NameList']['city_furigana']. $datas['NameList']['address_furigana'];
        $address_furigana = mb_convert_kana($address_furigana, "KVC");
        $pdf->SetFont($font, null, 9, true);
        $max_len = 30;
        if(mb_strlen($address_furigana) > $max_len){
            $address_furigana = mb_substr($address_furigana,0,$max_len);
        }
        $pdf->SetXY(89.6, 24.7);
        $pdf->Cell(70, 0, $address_furigana, 0, 0);

        //post_number(4)
        $post_number = $datas['NameList']['post_number'];
        $pdf->SetFont($font, null, 9, true);
        if(mb_strpos($datas['NameList']['post_number'], "-") === false){
            $post_number = mb_substr($datas['NameList']['post_number'],0,3);
            $post_number .= ' - '.mb_substr($datas['NameList']['post_number'],3);
        }
        $post_number_temp = explode("-", $post_number);
        $pdf->SetXY(95.5, 30.5);
        $pdf->Cell(20, 0, $post_number_temp[0], 0,'C');
        $pdf->SetXY(107.5, 30.5);
        $pdf->Cell(20, 0, $post_number_temp[1], 0,'C');

        //address(5)
        $address = $datas['NameList']['prefecture']. $datas['NameList']['city_furigana']. $datas['NameList']['address'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 30;
        $max_len = 60;
        if(mb_strlen($address) > $max_len){
            $address = mb_substr($address,0,$max_len);
        }
        if(mb_strlen($address) > $len_temp){
            $pdf->SetXY(89.6, 35);
            $pdf->Cell(70, 0, mb_substr($address,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(89.6, 39);
            $pdf->Cell(70, 0, mb_substr($address,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(89.6, 37);
            $pdf->Cell(70, 0, $address, 0, 0, 'L');
        }

        //phone_number(6)
        $phone = explode('-', $datas['NameList']['phone_number']);
        $pdf->SetFont($font, null, 9.5, true);
        $pdf->SetXY(146, 43.7);
        $pdf->Cell(8, 0, $phone[0], 0, 0, 'C');
        $pdf->SetXY(159, 43.7);
        $pdf->Cell(8, 0, $phone[1], 0, 0, 'C');
        $pdf->SetXY(172, 43.7);
        $pdf->Cell(8, 0, $phone[2], 0, 0, 'C');

        //name_furigana(7)
        $pdf->SetFont($font, null, 9, true);
        $pdf->SetXY(89.6, 50.5);
        $name_furigana = $datas['NameList']['name_furigana'];
        $name_furigana = mb_convert_kana($name_furigana, "KVC");
        $max_len = 30;
        if(mb_strlen($name_furigana) > $max_len){
            $name_furigana = mb_substr($name_furigana,0,$max_len);
        }
        $pdf->Cell(86, 0, $name_furigana, 0);

        //User.name(8)
        $name = $datas['User']['name'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 30;
        $max_len = 60;
        if(mb_strlen($name) > $max_len){
            $name = mb_substr($name,0,$max_len);
        }
        if(mb_strlen($name) > $len_temp){
            $pdf->SetXY(89.6, 58);
            $pdf->Cell(70, 0, mb_substr($name,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(89.6, 62.5);
            $pdf->Cell(70, 0, mb_substr($name,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(89.6, 58);
            $pdf->Cell(70, 0, $name, 0, 0, 'L');
        }
        //President.name(8)
        $name = $datas['President']['name'];
        $pdf->SetFont($font, null, 9, true);
        $max_len = 26;
        if(mb_strlen($name) > $max_len){
            $name = mb_substr($name,0,$max_len);
        }
        $pdf->SetXY(89.6, 68);
        $pdf->Cell(70, 0, $name, 0, 0, 'L');

        //company_number(9)
        $pdf->SetFont($font, null, 11, true);
        $numbers = preg_split("//u", $datas['User']['company_number'], -1, PREG_SPLIT_NO_EMPTY);

        foreach($numbers as $n_key => $numver) {
            if($n_key <= 2){
                $pdf->SetXY(88.6 + $n_key * 7.6, 80);
            } else if($n_key <= 4){
                $pdf->SetXY(90 + $n_key * 7.2, 80);
            } else if($n_key <= 6){
                $pdf->SetXY(90 + $n_key * 7.5, 80);
            } else {
                $pdf->SetXY(90 + $n_key * 7.6, 80);
            }
            $pdf->Cell(8, 0, $numver, 0);
        }

        //start_period(10)
        $pdf->SetFont($font, null, 9, true);
        $start_period = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['start_period']);

        $pdf->SetXY(79.5, 94);
        $pdf->Cell(8, 0, $start_period['year'], 0, 0, 'C');
        $pdf->SetXY(91.5, 94);
        $pdf->Cell(8, 0, $start_period['month'], 0, 0, 'C');
        $pdf->SetXY(104, 94);
        $pdf->Cell(8, 0, $start_period['day'], 0, 0, 'C');

        //end_period(10)
        $end_period = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['end_period']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(143, 94);
        $pdf->Cell(8, 0, $end_period['year'], 0, 0, 'C');
        $pdf->SetXY(156, 94);
        $pdf->Cell(8, 0, $end_period['month'], 0, 0, 'C');
        $pdf->SetXY(169, 94);
        $pdf->Cell(8, 0, $end_period['day'], 0, 0, 'C');

        //base_start_period(11)
        $base_start_period = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['base_start_period']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(79.5, 100.9);
        $pdf->Cell(8, 0, $base_start_period['year'], 0, 0, 'C');
        $pdf->SetXY(91.5, 100.9);
        $pdf->Cell(8, 0, $base_start_period['month'], 0, 0, 'C');
        $pdf->SetXY(104, 100.9);
        $pdf->Cell(8, 0, $base_start_period['day'], 0, 0, 'C');

        //base_end_period(11)
        $base_end_period = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['base_end_period']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(143, 101);
        $pdf->Cell(8, 0, $base_end_period['year'], 0, 0, 'C');
        $pdf->SetXY(156, 101);
        $pdf->Cell(8, 0, $base_end_period['month'], 0, 0, 'C');
        $pdf->SetXY(169, 101);
        $pdf->Cell(8, 0, $base_end_period['day'], 0, 0, 'C');

        //base_sales(12)
        $base_sales = $datas['ConsumptionTaxEasyNotification']['base_sales'];
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(137, 108);
        $pdf->Cell(40, 0, number_format($base_sales), 0, 0, 'R');

        //business_content(13)
        $business_content = $datas['ConsumptionTaxEasyNotification']['business_content'];
        $pdf->SetFont($font, null, 9, true);
        $len_temp = 31;
        $max_len = 62;
        if(mb_strlen($business_content) > $max_len){
            $business_content = mb_substr($business_content,0,$max_len);
        }
        if(mb_strlen($business_content) > $len_temp){
            $pdf->SetXY(57.5, 118);
            $pdf->Cell(70, 0, mb_substr($business_content,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(57.5, 122.5);
            $pdf->Cell(70, 0, mb_substr($business_content,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(57.5, 120);
            $pdf->Cell(70, 0, $business_content, 0, 0, 'L');
        }

        //business_class(14)
        $business_class = $datas['ConsumptionTaxEasyNotification']['business_class'];
        $pdf->SetXY(166, 120);
        $pdf->Cell(8, 0, $business_class, 0, 0, 'C');

        //requirement_class(15)
        $requirement_class = $datas['ConsumptionTaxEasyNotification']['requirement_class'];
		if(!empty($requirement_class)){
			if($requirement_class == 1){
				$pdf->SetXY(180.2, 129);
			} else {
				$pdf->SetXY(159, 129);
			}
			$pdf->Cell(8, 0, '✓', 0, 0, 'C');
		}

        //start_paying_date(16)
        $start_paying_date = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['start_paying_date']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(152, 136.6);
        $pdf->Cell(8, 0, $start_paying_date['year'], 0, 0, 'C');
        $pdf->SetXY(162, 136.6);
        $pdf->Cell(8, 0, $start_paying_date['month'], 0, 0, 'C');
        $pdf->SetXY(172, 136.6);
        $pdf->Cell(8, 0, $start_paying_date['day'], 0, 0, 'C');

        //not_special_class_i(17)
        $not_special_class_i = $datas['ConsumptionTaxEasyNotification']['not_special_class_i'];
        if($not_special_class_i == 1){
            $pdf->SetXY(179.5, 143.4);
            $pdf->Cell(8, 0, '✓', 0, 0, 'C');
        }

        //establishment_date(18)
        $establishment_date = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['establishment_date']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(152, 151.5);
        $pdf->Cell(8, 0, $establishment_date['year'], 0, 0, 'C');
        $pdf->SetXY(162, 151.5);
        $pdf->Cell(8, 0, $establishment_date['month'], 0, 0, 'C');
        $pdf->SetXY(172, 151.5);
        $pdf->Cell(8, 0, $establishment_date['day'], 0, 0, 'C');

        //not_special_class_ro(19)
        $not_special_class_ro = $datas['ConsumptionTaxEasyNotification']['not_special_class_ro'];
        if($not_special_class_ro == 1){
            $pdf->SetXY(179.5, 161.4);
            $pdf->Cell(8, 0, '✓', 0, 0, 'C');
        }

        //buying_start_period_date_a(20)
        $buying_start_period_date_a = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['buying_start_period_date_a']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(152, 171.6);
        $pdf->Cell(8, 0, $buying_start_period_date_a['year'], 0, 0, 'C');
        $pdf->SetXY(162, 171.6);
        $pdf->Cell(8, 0, $buying_start_period_date_a['month'], 0, 0, 'C');
        $pdf->SetXY(172, 171.6);
        $pdf->Cell(8, 0, $buying_start_period_date_a['day'], 0, 0, 'C');

        //not_special_class_ha_a(21)
        $not_special_class_ha_a = $datas['ConsumptionTaxEasyNotification']['not_special_class_ha_a'];
        if($not_special_class_ha_a == 1){
            $pdf->SetXY(179.5, 180.5);
            $pdf->Cell(8, 0, '✓', 0, 0, 'C');
        }

        //buying_start_period_date_b(22)
        $buying_start_period_date_b = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['buying_start_period_date_b']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(152, 190.4);
        $pdf->Cell(8, 0, $buying_start_period_date_b['year'], 0, 0, 'C');
        $pdf->SetXY(162, 190.4);
        $pdf->Cell(8, 0, $buying_start_period_date_b['month'], 0, 0, 'C');
        $pdf->SetXY(172, 190.4);
        $pdf->Cell(8, 0, $buying_start_period_date_b['day'], 0, 0, 'C');

        //build_start_period_date(23)
        $build_start_period_date = $this->convertHeiseiDate($datas['ConsumptionTaxEasyNotification']['build_start_period_date']);
        $pdf->SetFont($font, null, 9, true);

        $pdf->SetXY(152, 196.7);
        $pdf->Cell(8, 0, $build_start_period_date['year'], 0, 0, 'C');
        $pdf->SetXY(162, 196.7);
        $pdf->Cell(8, 0, $build_start_period_date['month'], 0, 0, 'C');
        $pdf->SetXY(172, 196.7);
        $pdf->Cell(8, 0, $build_start_period_date['day'], 0, 0, 'C');

        //not_special_class_ha_b(24)
        $not_special_class_ha_b = $datas['ConsumptionTaxEasyNotification']['not_special_class_ha_b'];
        if($not_special_class_ha_b == 1){
            $pdf->SetXY(179.5, 207.6);
            $pdf->Cell(8, 0, '✓', 0, 0, 'C');
        }

        //note(25)
        $note = $datas['ConsumptionTaxEasyNotification']['note'];
        $max_len = 80;
        $len_temp = 40;
        if(mb_strlen($note) > $max_len){
            $note = mb_substr($note, 0, $max_len);
        }
        $pdf->SetFont($font, null, 9, true);

        if(mb_strlen($note) > $len_temp){
            $pdf->SetXY(57.5, 228.5);
            $pdf->Cell(70, 0, mb_substr($note,0,$len_temp), 0, 0, 'L');
            $pdf->SetXY(57.5, 232.5);
            $pdf->Cell(70, 0, mb_substr($note,$len_temp), 0, 0, 'L');
        } else {
            $pdf->SetXY(57.5, 230.5);
            $pdf->Cell(70, 0, $note, 0, 0, 'L');
        }

        return $pdf;
    }


}
