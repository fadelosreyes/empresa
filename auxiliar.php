<?php

function conectar()
{
    return new PDO('pgsql:host=localhost;dbname=datos', 'datos', 'datos');
}

function obtener_get($par) {
    return isset($_GET[$par]) ? trim($_GET[$par]) : null;
}

function obtener_post($par) {
    return isset($_POST[$par]) ? trim($_POST[$par]) : null;
}

function selected($criterio, $valor)
{
    return $criterio == $valor ? 'selected' : '';
}

function volver_departamentos()
{
    header('Location: departamentos.php');
}

function departamento_por_id($id, ?PDO $pdo = null, $bloqueo = false): array|false
{
    $pdo = $pdo ?? conectar();
    $sql = 'SELECT * FROM departamentos WHERE id = :id';
    if ($bloqueo) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function departamento_por_codigo($codigo, ?PDO $pdo = null, $bloqueo = false): array|false
{
    $pdo = $pdo ?? conectar();
    $sql = 'SELECT * FROM departamentos WHERE codigo = :codigo';
    if ($bloqueo) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo]);
    return $stmt->fetch();
}


function anyadir_error($par, $mensaje, &$errores)
{
    if (!isset($errores[$par])) {
        $errores[$par] = [];
    }
    $errores[$par][] = $mensaje;
}

function comprobar_codigo($codigo, &$errores, ?PDO $pdo = null, $id = null)
{
    $pdo = $pdo ?? conectar();
    if ($codigo == '') {
        anyadir_error('codigo', 'El código no puede estar vacío', $errores);
    } elseif (mb_strlen($codigo) > 2) {
        anyadir_error('codigo', 'El código es demasiado largo', $errores);
    } else {
        $departamento = departamento_por_codigo($codigo, $pdo);
        if ($departamento !== false &&
            ($id === null || $departamento['id'] != $id)) {
            anyadir_error('codigo', 'Ese departamento ya existe', $errores);
        }
    }
}

function comprobar_denominacion($denominacion, &$errores, ?PDO $pdo = null)
{
    $pdo = $pdo ?? conectar();
    if ($denominacion == '') {
        anyadir_error('denominacion', 'La denominación no puede estar vacía', $errores);
    } elseif (mb_strlen($denominacion) > 255) {
        anyadir_error('denominacion', 'La denominación es demasiado larga', $errores);
    }
}

function comprobar_localidad(&$localidad, &$errores)
{
    if ($localidad == '') {
        $localidad = null;
    } elseif (mb_strlen($localidad) > 255) {
        anyadir_error('localidad', 'La localidad es demasiado larga', $errores);
    }
}

function comprobar_fecha_alta(&$fecha_alta, &$errores)
{
    $matches = [];
    if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})((T| )([0-9]{2}):([0-9]{2})(:([0-9]{2}))?)?$/', $fecha_alta, $matches) === 0) {
        anyadir_error('fecha_alta', 'La fecha tiene un formato incorrecto', $errores);
    } else {
        [$anyo, $mes, $dia] = [$matches[1], $matches[2], $matches[3]];
        if (!checkdate($mes, $dia, $anyo)) {
            anyadir_error('fecha_alta', 'La fecha es incorrecta', $errores);
        } else {
            if (count($matches) > 4) {
                [$horas, $minutos] = [$matches[6], $matches[7]];
                $segundos = '00';
                if ($horas > 23 || $minutos > 59) {
                    anyadir_error('fecha_alta', 'La hora es incorrecta', $errores);
                } elseif (count($matches) > 9) {
                    $segundos = $matches[9];
                    if ($segundos > 59) {
                        anyadir_error('fecha_alta', 'La hora es incorrecta', $errores);
                    }
                }
            }
        }
    }

    if (!isset($errores['fecha_alta'])) {
        $fecha_alta = "$anyo-$mes-$dia $horas:$minutos:$segundos";
        $dt = new DateTime($fecha_alta, new DateTimeZone('Europe/Madrid'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        $fecha_alta = $dt->format('Y-m-d H:i:s');
    }
}

function comprobar_id($id, ?PDO $pdo = null): array|false
{
    $pdo = $pdo ?? conectar();
    if (!isset($_GET['id'])) {
        return false;
    }
    $id = trim($_GET['id']);
    if (!ctype_digit($id)) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT *
                             FROM departamentos
                            WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function mostrar_errores($errores)
{
    foreach ($errores as $par => $mensajes) {
        foreach ($mensajes as $mensaje) { ?>
            <h2><?= $mensaje ?></h3><?php
        }
    }
}

function fecha_formateada($fecha, $incluir_hora = false)
{
    $fecha = new DateTime($fecha);
    $fecha->setTimezone(new DateTimeZone('Europe/Madrid'));
    if ($incluir_hora) {
        return $fecha->format('d-m-Y H:i:s');
    }
    return $fecha->format('d-m-Y');
}

function fecha_formulario($fecha, $incluir_hora = false)
{
    $fecha = new DateTime($fecha);
    $fecha->setTimezone(new DateTimeZone('Europe/Madrid'));
    if ($incluir_hora) {
        return $fecha->format('Y-m-d H:i:s');
    }
    return $fecha->format('Y-m-d');
}
