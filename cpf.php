<?php
error_reporting(0);

$contador_arquivo = file_get_contents('contador.txt'); // Lê o valor atual do contador
$contador = intval($contador_arquivo); // Converte para inteiro

$cpf = $_GET['cpf'];
$cpf = preg_replace('/\D/', '', $cpf);

if (empty($cpf)) {
    http_response_code(400);
    $result = "RESULTADO\n\nCPF não fornecido.";
    header('Content-Disposition: attachment; filename="resultado.txt"');
    header('Content-Type: text/plain; charset=utf-8');
    echo $result;
} else {
    $fusohorario = json_decode(file_get_contents("http://worldtimeapi.org/api/timezone/America/Sao_Paulo"));
    $unixtime = $fusohorario->unixtime;
    $datetime = date("d-m-Y H:i:s", $unixtime);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.legitimuz.com/external/kyc/cpf-history?cpf='.$cpf.'&token=176512e3-43e6-479a-8dd8-fd58fa8acb8d');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Host: api.legitimuz.com',
        'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
        'user-agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        'accept: */*',
        'origin: https://zapay.legitimuz.com',
        'referer: https://zapay.legitimuz.com/',
        'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,es;q=0.6'
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    $resposta = curl_exec($ch);

    $data = json_decode($resposta);

    if ($data && isset($data->dados)) {
        $dados = $data->dados;

        $result = "RESULTADO DA CONSULTA\n\n";
        $result .= "CPF: " . $dados->cpf . "\n";
        $result .= "Nome Completo: " . $dados->nome . "\n";
        $result .= "Nome da Mae: " . $dados->nome_mae . "\n";
        $result .= "Genero: " . $dados->genero . "\n";
        $result .= "Nascimento: " . date("d/m/Y", strtotime($dados->data_nascimento)) . "\n";
        $result .= "Idade: " . $dados->idade . " anos\n";
        $result .= "Signo: " . $dados->signo . "\n";
        $result .= "Nacionalidade: " . $dados->nacionalidade . "\n";
        $result .= "Situacao: " . $dados->outros_campos->situacao . "\n";
        $result .= "Estado: " . $dados->outros_campos->uf_cpf . "\n";
        $result .= "Origem: " . $dados->outros_campos->origem . "\n";
        $result .= "Nome Exclusivo: " . $dados->outros_campos->exclusive_name . "\n";
        $result .= "\nHorario: " . $datetime . "\n";

        // Incrementa o contador e salva o novo valor
        $contador++;
        file_put_contents('contador.txt', $contador);

        // Nome do arquivo com base no contador
        $nome_arquivo = $contador . '.txt';
        header('Content-Disposition: attachment; filename="'.$nome_arquivo.'"');
        header('Content-Type: text/plain; charset=utf-8');
        echo $result;
    } else {
        http_response_code(404);
        $result = "RESULTADO\n\nCPF não encontrado na base de dados.";
        header('Content-Disposition: attachment; filename="resultado.txt"');
        header('Content-Type: text/plain; charset=utf-8');
        echo $result;
    }
}
?>