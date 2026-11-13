// Alpine.js vem embutido no Livewire 3 — não instalar Alpine separado
// (duas instâncias quebram os componentes). Por isso o bundle manual.
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

import rvHistorico from './rv-historico';
import rvInfinito from './rv-infinito';
import rvPaginacao, { rvAnteriores } from './rv-paginacao';
import rvFavorito from './rv-favorito';
import rvTooltip from './rv-tooltip';

Alpine.data('rvInfinito', rvInfinito);
Alpine.data('rvTooltip', rvTooltip);
Alpine.data('rvFavorito', rvFavorito);
Alpine.data('rvPaginacao', rvPaginacao);
Alpine.data('rvAnteriores', rvAnteriores);

rvHistorico(Livewire);

Livewire.start();
