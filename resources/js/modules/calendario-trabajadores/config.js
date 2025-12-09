export const cfg = () => window.AppPlanif;
export const CSRF = () => cfg().csrf;
export const R = () => cfg().routes;
export const DATA = () => ({
    maquinas: cfg().maquinas,
    eventos: cfg().eventos,
    cargaTrabajo: cfg().cargaTrabajo || {},
    turnos: cfg().turnos || [],
});
