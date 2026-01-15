<template>
  <div class="bg-gray-50">
    <div class="max-w-[1440px] mx-auto p-4 sm:p-8">
      <div class="flex justify-between items-center mb-10">
        <h1 class="text-3xl font-bold flex-1 text-center sm:text-left flex items-center gap-3 text-gray-800">
          <font-awesome-icon :icon="['fas', 'chart-bar']" class="text-green-600" />
          Dashboard
        </h1>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 justify-center mb-10">
        <PeriodPicker @change="loadDashboard" />
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Filtrar por Setor:</label>
            <select v-model="selectedSector" @change="loadDashboard" 
              class="border rounded-lg p-2 text-sm min-w-[200px]">
              <option value="">Todos os Setores</option>
              <option v-for="sector in availableSectors" :key="sector" :value="sector">
                {{ sector }}
              </option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Visualizar:</label>
            <div class="flex bg-gray-100 rounded-lg p-1">
              <button 
                @click="viewMode = 'order'"
                :class="viewMode === 'order' 
                  ? 'bg-blue-600 text-white' 
                  : 'text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-md text-sm font-medium transition">
                Por Pedido
              </button>
              <button 
                @click="viewMode = 'registration'"
                :class="viewMode === 'registration' 
                  ? 'bg-blue-600 text-white' 
                  : 'text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-md text-sm font-medium transition">
                Por Inscrição
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading">
        <Loading />
      </div>

      <div v-else>
        <!-- Relatório por Pedido (Agrupado) - Mostrar quando viewMode === 'order' -->
        <div v-if="viewMode === 'order'" class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-8">
          <div class="p-6 border-b">
            <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'shopping-cart']" class="text-blue-600" />
              Relatório por Pedido
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Estatísticas agrupadas por pagamento (pedido). Quando uma pessoa compra 3 inscrições, conta como 1 pedido.
            </p>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
              <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <h3 class="text-xs font-medium text-blue-700 mb-1">Total Pedidos</h3>
                <p class="text-2xl font-bold text-blue-600">{{ stats?.orders?.total ?? 0 }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <h3 class="text-xs font-medium text-green-700 mb-1">Pedidos Pagos</h3>
                <p class="text-2xl font-bold text-green-600">{{ stats?.orders?.paid ?? 0 }}</p>
              </div>
              <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <h3 class="text-xs font-medium text-yellow-700 mb-1">Pedidos Pendentes</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ stats?.orders?.pending ?? 0 }}</p>
              </div>
              <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <h3 class="text-xs font-medium text-red-700 mb-1">Pedidos Cancelados</h3>
                <p class="text-2xl font-bold text-red-600">{{ stats?.orders?.canceled ?? 0 }}</p>
              </div>
              <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                <h3 class="text-xs font-medium text-orange-700 mb-1">Pedidos Vencidos</h3>
                <p class="text-2xl font-bold text-orange-600">{{ stats?.orders?.overdue ?? 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Relatório por Inscrição (Individual) - Mostrar quando viewMode === 'registration' -->
        <div v-if="viewMode === 'registration'" class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-8">
          <div class="p-6 border-b">
            <h2 class="text-xl font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'user-check']" class="text-green-600" />
              Relatório por Inscrição
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Estatísticas individuais. Quando um pedido com 3 inscrições é pago, todas as 3 inscrições são consideradas pagas.
            </p>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
              <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <h3 class="text-xs font-medium text-blue-700 mb-1">Total Inscrições</h3>
                <p class="text-2xl font-bold text-blue-600">{{ stats?.registrations?.total ?? stats?.orders_old ?? 0 }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <h3 class="text-xs font-medium text-green-700 mb-1">Inscrições Pagas</h3>
                <p class="text-2xl font-bold text-green-600">{{ stats?.registrations?.paid ?? stats?.paid_old ?? 0 }}</p>
              </div>
              <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <h3 class="text-xs font-medium text-yellow-700 mb-1">Inscrições Pendentes</h3>
                <p class="text-2xl font-bold text-yellow-600">{{ stats?.registrations?.pending ?? stats?.pending_old ?? 0 }}</p>
              </div>
              <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <h3 class="text-xs font-medium text-red-700 mb-1">Inscrições Canceladas</h3>
                <p class="text-2xl font-bold text-red-600">{{ stats?.registrations?.canceled ?? stats?.canceled_old ?? 0 }}</p>
              </div>
              <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                <h3 class="text-xs font-medium text-orange-700 mb-1">Inscrições Vencidas</h3>
                <p class="text-2xl font-bold text-orange-600">{{ stats?.registrations?.overdue ?? stats?.overdue_old ?? 0 }}</p>
              </div>
              <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <h3 class="text-xs font-medium text-emerald-700 mb-1">Inscrições Validadas</h3>
                <p class="text-2xl font-bold text-emerald-600">{{ stats?.registrations?.validated ?? stats?.validated_old ?? 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'user']" />
              Usuários
            </h2>
            <p class="text-3xl font-bold text-orange-600">{{ stats?.users ?? 0 }}</p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'money-bill-wave']" />
              Faturamento
            </h2>
            <p class="text-3xl font-bold text-indigo-600">
              {{ formatCurrency(stats?.value_paid ?? 0) }}
            </p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'wallet']" />
              Total Valores
            </h2>
            <p class="text-3xl font-bold text-gray-700">
              {{ formatCurrency(stats?.value_total ?? 0) }}
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'hourglass-half']" />
              Valores Pendentes
            </h2>
            <p class="text-3xl font-bold text-yellow-600">
              {{ formatCurrency(stats?.value_pending ?? 0) }}
            </p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'times-circle']" />
              Valores Cancelados
            </h2>
            <p class="text-3xl font-bold text-red-600">
              {{ formatCurrency(stats?.value_canceled ?? 0) }}
            </p>
          </div>

          <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition p-5">
            <h2 class="text-gray-500 text-sm flex items-center gap-2">
              <font-awesome-icon :icon="['fas', 'exclamation-triangle']" />
              Valores Vencidos
            </h2>
            <p class="text-3xl font-bold text-orange-600">
              {{ formatCurrency(stats?.value_overdue ?? 0) }}
            </p>
          </div>
        </div>

        <!-- Relatório de Inscrições -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'file-lines']" class="text-green-600" />
              Relatório de Inscrições
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Período: {{ formatDateOnly(period?.start) }} até {{ formatDateOnly(period?.end) }}
            </p>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Total de Inscrições -->
              <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-blue-700">Total de Inscrições</h3>
                  <font-awesome-icon :icon="['fas', 'users']" class="text-blue-500" />
                </div>
                <p class="text-3xl font-bold text-blue-600">{{ stats?.registrations?.total ?? stats?.orders_old ?? 0 }}</p>
              </div>

              <!-- Inscrições Pagas -->
              <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-green-700">Inscrições Pagas</h3>
                  <font-awesome-icon :icon="['fas', 'check-circle']" class="text-green-500" />
                </div>
                <p class="text-3xl font-bold text-green-600">{{ stats?.registrations?.paid ?? stats?.paid_old ?? 0 }}</p>
              </div>

              <!-- Inscrições Não Pagas -->
              <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-yellow-700">Inscrições Não Pagas</h3>
                  <font-awesome-icon :icon="['fas', 'clock']" class="text-yellow-500" />
                </div>
                <p class="text-3xl font-bold text-yellow-600">{{ stats?.registrations?.pending ?? stats?.pending_old ?? 0 }}</p>
              </div>

              <!-- Inscrições Canceladas -->
              <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-red-700">Inscrições Canceladas</h3>
                  <font-awesome-icon :icon="['fas', 'times-circle']" class="text-red-500" />
                </div>
                <p class="text-3xl font-bold text-red-600">{{ stats?.registrations?.canceled ?? stats?.canceled_old ?? 0 }}</p>
              </div>

              <!-- Inscrições Vencidas -->
              <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-orange-700">Inscrições Vencidas</h3>
                  <font-awesome-icon :icon="['fas', 'exclamation-triangle']" class="text-orange-500" />
                </div>
                <p class="text-3xl font-bold text-orange-600">{{ stats?.registrations?.overdue ?? stats?.overdue_old ?? 0 }}</p>
              </div>

              <!-- Inscrições Validadas -->
              <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-emerald-700">Inscrições Validadas</h3>
                  <font-awesome-icon :icon="['fas', 'check-double']" class="text-emerald-500" />
                </div>
                <p class="text-3xl font-bold text-emerald-600">{{ stats?.registrations?.validated ?? stats?.validated_old ?? 0 }}</p>
              </div>

              <!-- Pagas por PIX -->
              <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-emerald-700">Pagas por PIX</h3>
                  <font-awesome-icon :icon="['fab', 'pix']" class="text-emerald-500" />
                </div>
                <p class="text-3xl font-bold text-emerald-600">{{ stats?.paid_by_pix ?? 0 }}</p>
              </div>

              <!-- Pagas por Boleto -->
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-gray-700">Pagas por Boleto</h3>
                  <font-awesome-icon :icon="['fas', 'barcode']" class="text-gray-500" />
                </div>
                <p class="text-3xl font-bold text-gray-600">{{ stats?.paid_by_boleto ?? 0 }}</p>
              </div>

              <!-- Pagas por Cartão -->
              <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                <div class="flex items-center justify-between mb-2">
                  <h3 class="text-sm font-medium text-indigo-700">Pagas por Cartão</h3>
                  <font-awesome-icon :icon="['fas', 'credit-card']" class="text-indigo-500" />
                </div>
                <p class="text-3xl font-bold text-indigo-600">{{ stats?.paid_by_card ?? 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Relatório Detalhado - Pagos (mostrar baseado no viewMode) -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'chart-pie']" class="text-green-600" />
              Relatório Detalhado - <span v-if="viewMode === 'order'">Pedidos</span><span v-else>Inscrições</span> Pagos/Pagas
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Distribuição <span v-if="viewMode === 'order'">dos pedidos pagos</span><span v-else>das inscrições pagas</span> por categoria
            </p>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- Por Setor -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-blue-500 text-xl" />
                  Por Setor
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_paid_by_sector : stats?.paid_by_sector) && Object.keys(viewMode === 'order' ? stats.orders_paid_by_sector : stats.paid_by_sector).length > 0" class="space-y-3">
                  <div v-for="(count, sector) in (viewMode === 'order' ? stats.orders_paid_by_sector : stats.paid_by_sector)" :key="sector" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-blue-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-blue-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ sector || 'Não informado' }}</span>
                    </div>
                    <span class="text-2xl font-bold text-blue-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>

              <!-- Por Sexo -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'venus-mars']" class="text-pink-500 text-xl" />
                  Por Sexo
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_paid_by_gender : stats?.paid_by_gender) && Object.keys(viewMode === 'order' ? stats.orders_paid_by_gender : stats.paid_by_gender).length > 0" class="space-y-3">
                  <div v-for="(count, gender) in (viewMode === 'order' ? stats.orders_paid_by_gender : stats.paid_by_gender)" :key="gender" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-pink-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon 
                          :icon="gender === 'MASCULINO' ? ['fas', 'mars'] : ['fas', 'venus']" 
                          class="text-pink-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ gender === 'MASCULINO' ? 'Masculino' : 'Feminino' }}</span>
                    </div>
                    <span class="text-2xl font-bold text-pink-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>

              <!-- Por Tipo -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'user-tag']" class="text-purple-500 text-xl" />
                  Por Tipo
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_paid_by_type : stats?.paid_by_type) && Object.keys(viewMode === 'order' ? stats.orders_paid_by_type : stats.paid_by_type).length > 0" class="space-y-3">
                  <div v-for="(count, type) in (viewMode === 'order' ? stats.orders_paid_by_type : stats.paid_by_type)" :key="type" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-purple-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon :icon="['fas', 'user']" class="text-purple-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ formatChurchType(type) }}</span>
                    </div>
                    <span class="text-2xl font-bold text-purple-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>

              <!-- Por Congregação -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'city']" class="text-teal-500 text-xl" />
                  Por Congregação
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_paid_by_city : stats?.paid_by_city) && Object.keys(viewMode === 'order' ? stats.orders_paid_by_city : stats.paid_by_city).length > 0" 
                  class="space-y-3 max-h-80 overflow-y-auto pr-2">
                  <div v-for="(count, city) in (viewMode === 'order' ? stats.orders_paid_by_city : stats.paid_by_city)" :key="city" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-teal-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon :icon="['fas', 'building']" class="text-teal-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ city || 'Não informado' }}</span>
                    </div>
                    <span class="text-2xl font-bold text-teal-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Relatório Detalhado - Não Pagos (mostrar baseado no viewMode) -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'chart-line']" class="text-yellow-600" />
              Relatório Detalhado - <span v-if="viewMode === 'order'">Pedidos</span><span v-else>Inscrições</span> Não Pagos/Pagas
            </h2>
            <p class="text-sm text-gray-500 mt-1">
              Distribuição <span v-if="viewMode === 'order'">dos pedidos não pagos</span><span v-else>das inscrições não pagas</span> por categoria
            </p>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- Por Setor -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-yellow-500 text-xl" />
                  Por Setor
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_pending_by_sector : stats?.pending_by_sector) && Object.keys(viewMode === 'order' ? stats.orders_pending_by_sector : stats.pending_by_sector).length > 0" class="space-y-3">
                  <div v-for="(count, sector) in (viewMode === 'order' ? stats.orders_pending_by_sector : stats.pending_by_sector)" :key="sector" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-yellow-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-yellow-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ sector || 'Não informado' }}</span>
                    </div>
                    <span class="text-2xl font-bold text-yellow-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>

              <!-- Por Sexo -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'venus-mars']" class="text-yellow-500 text-xl" />
                  Por Sexo
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_pending_by_gender : stats?.pending_by_gender) && Object.keys(viewMode === 'order' ? stats.orders_pending_by_gender : stats.pending_by_gender).length > 0" class="space-y-3">
                  <div v-for="(count, gender) in (viewMode === 'order' ? stats.orders_pending_by_gender : stats.pending_by_gender)" :key="gender" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-yellow-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon 
                          :icon="gender === 'MASCULINO' ? ['fas', 'mars'] : ['fas', 'venus']" 
                          class="text-yellow-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ gender === 'MASCULINO' ? 'Masculino' : 'Feminino' }}</span>
                    </div>
                    <span class="text-2xl font-bold text-yellow-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>

              <!-- Por Tipo -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'user-tag']" class="text-yellow-500 text-xl" />
                  Por Tipo
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_pending_by_type : stats?.pending_by_type) && Object.keys(viewMode === 'order' ? stats.orders_pending_by_type : stats.pending_by_type).length > 0" class="space-y-3">
                  <div v-for="(count, type) in (viewMode === 'order' ? stats.orders_pending_by_type : stats.pending_by_type)" :key="type" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-yellow-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon :icon="['fas', 'user']" class="text-yellow-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ formatChurchType(type) }}</span>
                    </div>
                    <span class="text-2xl font-bold text-yellow-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>

              <!-- Por Congregação -->
              <div class="bg-gray-50 rounded-xl p-5">
                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                  <font-awesome-icon :icon="['fas', 'city']" class="text-yellow-500 text-xl" />
                  Por Congregação
                </h3>
                <div v-if="(viewMode === 'order' ? stats?.orders_pending_by_city : stats?.pending_by_city) && Object.keys(viewMode === 'order' ? stats.orders_pending_by_city : stats.pending_by_city).length > 0" 
                  class="space-y-3 max-h-80 overflow-y-auto pr-2">
                  <div v-for="(count, city) in (viewMode === 'order' ? stats.orders_pending_by_city : stats.pending_by_city)" :key="city" 
                    class="flex items-center justify-between p-4 bg-white rounded-lg border border-yellow-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <font-awesome-icon :icon="['fas', 'building']" class="text-yellow-600" />
                      </div>
                      <span class="font-medium text-gray-800">{{ city || 'Não informado' }}</span>
                    </div>
                    <span class="text-2xl font-bold text-yellow-600">{{ count }}</span>
                  </div>
                </div>
                <div v-else class="text-center py-8">
                  <font-awesome-icon :icon="['fas', 'inbox']" class="text-gray-300 text-4xl mb-2" />
                  <p class="text-gray-500 text-sm">Nenhum dado disponível</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition overflow-hidden mb-10">
          <div class="p-6 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold flex items-center gap-2 text-gray-800">
              <font-awesome-icon :icon="['fas', 'user-check']" class="text-green-600" />
              Últimas Inscrições
            </h2>
            <span v-if="latestOrders.length" class="text-sm text-gray-500">
              Total: {{ latestOrders.length }}
            </span>
          </div>

          <div class="p-6">
            <div v-if="latestOrders.length" class="overflow-x-auto">
              <table class="w-full text-sm border rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                  <tr>
                    <th class="p-3 border text-left">Pagamento</th>
                    <th class="p-3 border text-left">Cliente</th>
                    <th class="p-3 border text-left">Inscrições</th>
                    <th class="p-3 border text-left">Valor</th>
                    <th class="p-3 border text-left">Status</th>
                    <th class="p-3 border text-left">Método</th>
                    <th class="p-3 border text-left">Data</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="o in latestOrders" :key="o.payment_id || o.id" class="hover:bg-gray-50 transition">
                    <td class="p-3 border">
                      <span class="text-blue-600 font-medium font-mono text-xs">
                        {{ o.payment_id || o.id }}
                      </span>
                    </td>
                    <td class="p-3 border text-gray-800 truncate max-w-[180px]">
                      {{ o.buyer_name || o.registrations?.[0]?.name || '-' }}
                    </td>
                    <td class="p-3 border">
                      <span class="font-semibold text-gray-900">{{ o.registrations_count || o.registrations?.length || 0 }}</span>
                      <div v-if="o.registrations && o.registrations.length > 0" class="text-xs text-gray-600 mt-1">
                        <div v-for="reg in o.registrations.slice(0, 2)" :key="reg.id" class="truncate">
                          • {{ reg.name }} - {{ reg.event?.name || 'Evento' }}
                        </div>
                        <div v-if="o.registrations.length > 2" class="text-gray-500">
                          +{{ o.registrations.length - 2 }} mais
                        </div>
                      </div>
                    </td>
                    <td class="p-3 border">
                      <span class="font-semibold text-gray-900">{{ displayTotal(o) }}</span>
                      <div v-if="isCardInstallments(o)" class="text-xs text-gray-600">
                        Parcelado em {{ installmentCount(o) }}x de R$
                        {{ perInstallment(o) }}
                      </div>
                    </td>
                    <td class="p-3 border">
                      <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                        :class="statusPillClass(statusGateway(o))">
                        <font-awesome-icon :icon="statusIcon(statusGateway(o))" />
                        {{ statusLabel(statusGateway(o)) }}
                      </span>
                    </td>
                    <td class="p-3 border ">
                      <font-awesome-icon v-if="methodLabel(o) === 'PIX'" :icon="['fab', 'pix']"
                        class="text-green-600" />
                      <font-awesome-icon v-else-if="methodLabel(o) === 'Boleto'" :icon="['fas', 'barcode']"
                        class="text-gray-600" />
                      <font-awesome-icon v-else-if="methodLabel(o) === 'Cartão de Crédito'"
                        :icon="['fas', 'credit-card']" class="text-blue-600" />
                      <span>{{ methodLabel(o) }}</span>
                    </td>
                    <td class="p-3 border text-gray-700">
                      {{ formatDateBR(o.created_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p v-else class="text-gray-500 text-sm text-center py-6">
              Nenhuma inscrição encontrada.
            </p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import http from '@/api/http'
import PeriodPicker from '@/components/PeriodPicker.vue'
import Loading from '@/components/Loading.vue'
import { format, startOfMonth } from 'date-fns'

const stats = ref({})
const latestOrders = ref([])
const period = ref({})
const availableSectors = ref([])
const selectedSector = ref('')
const viewMode = ref('order') // 'order' ou 'registration'
const loading = ref(false)

function formatDateBR(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', timeZone: 'America/Sao_Paulo'
  }).format(new Date(value))
}

function formatDateOnly(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    timeZone: 'America/Sao_Paulo'
  }).format(new Date(value))
}

function formatChurchType(type) {
  const types = {
    'membro': 'Membro',
    'diacono': 'Diácono',
    'diaconisa': 'Diaconisa',
    'presbitero': 'Presbítero',
    'missionaria': 'Missionária',
    'evangelista': 'Evangelista',
    'pastor': 'Pastor',
    'missionario': 'Missionário',
    'visitante': 'Visitante',
    'outra_igreja': 'Outra Igreja'
  }
  return types[type?.toLowerCase()] || type || 'Não informado'
}

function formatCurrency(cents) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(cents / 100)
}

function statusLabel(status) {
  switch (status) {
    case 'pending': return 'Pendente'
    case 'paid': return 'Pago'
    case 'canceled': return 'Cancelado'
    case 'overdue': return 'Vencido'
    default: return status
  }
}

function statusGateway(o) {
  if (o.gateway_payload?.status) {
    const s = o.gateway_payload.status
    if (s === 'RECEIVED' || s === 'CONFIRMED') return 'paid'
    if (s === 'PENDING') return 'pending'
    if (s === 'CANCELLED' || s === 'DELETED') return 'canceled'
    if (s === 'OVERDUE') return 'overdue'
  }
  return o.status
}

function statusPillClass(status) {
  if (status === 'paid') return 'text-green-600 font-semibold'
  if (status === 'pending') return 'text-yellow-600 font-semibold'
  if (status === 'canceled') return 'text-red-600 font-semibold'
  if (status === 'overdue') return 'text-orange-600 font-semibold'
  return 'text-gray-600 font-semibold'
}

function methodLabel(o) {
  const t = (o.gateway_payload?.billingType || o.payment_method || '').toUpperCase()
  if (t === 'PIX') return 'PIX'
  if (t === 'BOLETO') return 'Boleto'
  if (t === 'CREDIT_CARD') return 'Cartão de Crédito'
  return '—'
}

function fmtLocalDate(d) {
  return format(new Date(d.getFullYear(), d.getMonth(), d.getDate()), 'yyyy-MM-dd')
}

function isCardInstallments(o) {
  const t = (o?.gateway_payload?.billingType || o?.payment_method || '').toUpperCase()
  if (t !== 'CREDIT_CARD') return false
  const cnt = Number(o?.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return true
  const desc = o?.gateway_payload?.description || ''
  return /Parcela\s+\d+\s+de\s+\d+/i.test(desc)
}

function installmentCount(o) {
  const cnt = Number(o?.gateway_payload?.installmentCount || 0)
  if (cnt > 1) return cnt
  const desc = o?.gateway_payload?.description || ''
  const m = desc.match(/de\s+(\d+)/i)
  return m ? Number(m[1]) : 1
}

function statusIcon(status) {
  if (status === 'paid') return ['fas', 'check-circle']
  if (status === 'pending') return ['fas', 'hourglass-half']
  if (status === 'canceled') return ['fas', 'times-circle']
  if (status === 'overdue') return ['fas', 'exclamation-triangle']
  return ['fas', 'hourglass-half']
}

function perInstallment(o) {
  const gp = o?.gateway_payload || {}
  if (gp.installmentValue != null) {
    return formatCurrency(Math.round(Number(gp.installmentValue) * 100))
  }
  if (gp.value != null && installmentCount(o) > 1) {
    return formatCurrency(Math.round(Number(gp.value) * 100))
  }
  return null
}

function displayTotal(o) {
  const gp = o?.gateway_payload || {}
  if (gp.totalValue != null) {
    return formatCurrency(Math.round(Number(gp.totalValue) * 100))
  }
  if (isCardInstallments(o)) {
    const count = installmentCount(o)
    const per = gp.installmentValue != null
      ? Math.round(Number(gp.installmentValue) * 100)
      : gp.value != null ? Math.round(Number(gp.value) * 100) : 0
    if (per && count > 1) {
      return formatCurrency(per * count)
    }
  }
  if (gp.value != null) {
    return formatCurrency(Math.round(Number(gp.value) * 100))
  }
  // Para registrations, usar total_amount se disponível, senão somar price_paid
  if (o.total_amount != null) {
    return formatCurrency(Number(o.total_amount))
  }
  if (o.registrations && Array.isArray(o.registrations)) {
    const total = o.registrations.reduce((sum, reg) => sum + (reg.price_paid || 0), 0)
    return formatCurrency(total)
  }
  return formatCurrency(0)
}

async function loadDashboard(range = []) {
  try {
    loading.value = true
    const params = {}
    if (Array.isArray(range) && range.length === 2 && range[0] && range[1]) {
      params.start_date = fmtLocalDate(range[0])
      params.end_date = fmtLocalDate(range[1])
    } else {
      const today = new Date()
      params.start_date = format(startOfMonth(today), 'yyyy-MM-dd')
      params.end_date = format(today, 'yyyy-MM-dd')
    }
    // Adicionar filtro de setor se selecionado
    if (selectedSector.value) {
      params.sector = selectedSector.value
    }
    const { data } = await http.get('/admin/dashboard', { params })
    latestOrders.value = data.latestOrders ?? []
    stats.value = data.stats ?? {}
    period.value = data.period ?? {}
    availableSectors.value = data.availableSectors ?? []
  } catch (e) {
    console.error('Erro ao carregar dashboard', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadDashboard())
</script>
