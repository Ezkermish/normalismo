SELECT
  x.actividad,
  x.tipoActividad,
  x.participante
FROM (
  -- ALUMNOS
  SELECT
    act.descripcion AS actividad,
    act.tipoActividad,
    CONCAT_WS(' ', al.nombre, al.apPaterno, al.apMaterno) AS participante
  FROM participaciones p
  INNER JOIN actividades act ON act.idActividad = p.idActividad
  INNER JOIN alumnos al ON al.idAlumno = p.idAlumno
  WHERE p.tipoParticipante = 'ALUMNO'
    AND al.cct = '15ENL0001E' --sustituir el cct de la escuela a buscar

  UNION ALL

  -- DOCENTES
  SELECT
    act.descripcion AS actividad,
    act.tipoActividad,
    d.nombre AS participante
  FROM participaciones p
  INNER JOIN actividades act ON act.idActividad = p.idActividad
  INNER JOIN docentes d ON d.idDocente = p.idDocente
  WHERE p.tipoParticipante = 'DOCENTE'
    AND d.escuela = '15ENL0001E' --sustituir el cct de la escuela a buscar
) x
ORDER BY
  x.tipoActividad, x.actividad, x.participante;