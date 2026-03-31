   <?php
   
   use App\Models\Invoice;
   use App\Models\Product;
   use App\Models\User;
   use App\Models\Warehouse;
   use Carbon\Carbon;
   use Illuminate\Support\Facades\DB;
   use Livewire\Attributes\Url;
   use Livewire\Component;
   
   new class extends Component {
       #[Url]
       public $year;
   
       public function mount()
       {
           #$this->year = request()->query('year', now()->year);
           $this->year = request()->query('year', '2021');
       }
   
       private function calculateRevenueGrowth($year)
       {
           $currentYearRevenue = $this->getTotalRevenue($year);
   
           $previousYearRevenue = $this->getTotalRevenue($year - 1);
   
           if ($previousYearRevenue > 0) {
               return round((($currentYearRevenue - $previousYearRevenue) / $previousYearRevenue) * 100, 1);
           }
   
           return 0;
       }
   
       private function getTotalRevenue($year)
       {
           return Invoice::whereYear('invoice_date', $year)->where('status', 'paid')->sum('total');
       }
   
       private function getRevenueData()
       {
           $data = Invoice::whereYear('invoice_date', $this->year)->where('status', 'paid')->get()->groupBy(fn($invoice) => $invoice->invoice_date->format('n'));
   
           return collect(range(1, 12))->map(function ($month) use ($data) {
               $monthData = $data->get((string) $month);
   
               return [
                   'label' => Carbon::create()->month($month)->format('M'),
                   'value' => $monthData ? $monthData->sum('total') : 0,
               ];
           });
       }
   
       private function getProductsData()
       {
           $data = Product::whereYear('created_at', $this->year)->get()->groupBy(fn($product) => $product->created_at->format('n'));
   
           return collect(range(1, 12))->map(function ($month) use ($data) {
               $monthData = $data->get((string) $month);
   
               return [
                   'label' => Carbon::create()->month($month)->format('M'),
                   'value' => $monthData ? $monthData->count() : 0,
               ];
           });
       }
   
       private function getInvoicesData()
       {
           $colors = [
               'draft' => '#6B7280',
               'issued' => '#3B82F6',
               'paid' => '#10B981',
               'overdue' => '#EF4444',
               'cancelled' => '#1F2937',
           ];
   
           return Invoice::select('status', DB::raw('COUNT(*) as count'))
               ->whereYear('invoice_date', $this->year)
               ->groupBy('status')
               ->get()
               ->filter(fn($row) => $row->count > 0)
               ->map(
                   fn($row) => [
                       'label' => ' ' . ucfirst($row->status),
                       'value' => (int) $row->count,
                       'color' => $colors[$row->status],
                   ],
               )
               ->values();
       }
   
       public function getAvailableYears()
       {
           $invoiceYears = Invoice::pluck('invoice_date')->filter()->map(fn($date) => Carbon::parse($date)->year);
   
           $productYears = Product::pluck('created_at')->filter()->map(fn($date) => Carbon::parse($date)->year);
   
           $years = $invoiceYears->merge($productYears)->unique()->sortDesc()->values();
   
           return $years->isEmpty() ? collect([now()->year]) : $years;
       }
   
       public function with()
       {
           $year = (int) $this->year;
           $now = now();
   
           $inventoryValue = Product::whereYear('created_at', $year)->sum(DB::raw('price * quantity'));
   
           $inventoryGrowth = 0;
   
           // Growth only makes sense for the current year
           if ($year === $now->year) {
               $currentMonthValue = Product::whereYear('created_at', $year)->whereMonth('created_at', $now->month)->sum(DB::raw('price * quantity'));
   
               $previousMonth = $now->copy()->subMonth();
   
               $lastMonthValue = Product::whereYear('created_at', $previousMonth->year)->whereMonth('created_at', $previousMonth->month)->sum(DB::raw('price * quantity'));
   
               if ($lastMonthValue > 0) {
                   $inventoryGrowth = round((($currentMonthValue - $lastMonthValue) / $lastMonthValue) * 100, 1);
               }
           }
   
           // Calculate revenue growth percentage
           $revenueGrowth = $this->calculateRevenueGrowth($year);
   
           return [
               'warehousesCount' => Warehouse::whereYear('created_at', $year)->count(),
               'productsCount' => Product::whereYear('created_at', $year)->count(),
               'totalQuantity' => Product::whereYear('created_at', $year)->sum('quantity'),
               'inventoryValue' => $inventoryValue,
               'lowStock' => Product::whereYear('created_at', $year)->where('quantity', '<', 10)->count(),
               'inventoryGrowth' => $inventoryGrowth,
               'userCount' => User::count(),
               'revenueData' => $this->getRevenueData(),
               'productsData' => $this->getProductsData(),
               'invoicesData' => $this->getInvoicesData(),
               'currentYear' => $year,
               'availableYears' => $this->getAvailableYears(),
               'totalRevenue' => $this->getTotalRevenue($year),
               'revenueGrowth' => $revenueGrowth,
           ];
       }
   };
   ?>
   <div class="space-y-4">
       <div class="flex items-center gap-5">
           <div>
               <flux:heading size="lg" class="mb-4">
                   Statistics
               </flux:heading>
               <flux:subheading>
                   Overview of key metrics and performance indicators.
               </flux:subheading>
           </div>
           <flux:select wire:model.live="year" class="w-48 ml-auto">
               @foreach ($availableYears as $availableYear)
                   <flux:select.option value="{{ $availableYear }}">
                       {{ $availableYear }}
                   </flux:select.option>
               @endforeach
           </flux:select>
       </div>


       <!-- Stats Cards -->
       <div class="grid gap-6 grid-cols-2 md:grid-cols-6">
           <!-- Total Revenue -->
           <flux:card>
               @island(lazy: true, always: true)
                   @placeholder
                       <div class="space-y-1">
                           <flux:text>
                               <flux:skeleton class="h-4 w-32" />
                           </flux:text>

                           <flux:heading size="xl" class="mb-1">
                               <flux:skeleton class="h-8 w-40" />
                           </flux:heading>

                           <div class="flex items-center gap-2">
                               <flux:skeleton class="h-4 w-4 rounded-full" />
                               <flux:skeleton class="h-4 w-12" />
                           </div>
                       </div>
                   @endplaceholder
                   <flux:text>Total Revenue</flux:text>
                   <flux:heading size="xl" class="mb-1">
                       ${{ number_format($totalRevenue, 2) }}
                   </flux:heading>
                   <div class="flex items-center gap-2">
                       <flux:icon.arrow-trending-up variant="micro"
                           class="{{ $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}" />
                       <span class="text-sm {{ $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                           {{ $revenueGrowth }}%
                       </span>
                   </div>
               @endisland
           </flux:card>

           <!-- Products -->
           <flux:card>
               <flux:text>Products</flux:text>
               <flux:heading size="xl" class="mb-1">
                   {{ $productsCount }}
               </flux:heading>
           </flux:card>

           <!-- Users -->
           <flux:card>
               <flux:text>Users</flux:text>
               <flux:heading size="xl" class="mb-1">
                   {{ $userCount }}
               </flux:heading>
           </flux:card>

           <!-- Total Stock -->
           <flux:card>
               <flux:text>Total Stock</flux:text>
               <flux:heading size="xl" class="mb-1">
                   {{ number_format($totalQuantity) }}
               </flux:heading>
               <span class="text-sm text-gray-500">Units</span>
           </flux:card>

           <!-- Inventory Value -->

           <!-- Total Revenue -->
           <flux:card>
               @island(lazy: true, always: true)
                   @placeholder
                       <div class="space-y-1">
                           <flux:text>
                               <flux:skeleton class="h-4 w-32" />
                           </flux:text>

                           <flux:heading size="xl" class="mb-1">
                               <flux:skeleton class="h-8 w-40" />
                           </flux:heading>

                           <div class="flex items-center gap-2">
                               <flux:skeleton class="h-4 w-4 rounded-full" />
                               <flux:skeleton class="h-4 w-12" />
                           </div>
                       </div>
                   @endplaceholder
                   <flux:text>Inventory Value</flux:text>
                   <flux:heading size="xl" class="mb-1 break-all">
                       ${{ number_format($inventoryValue, 2) }}
                   </flux:heading>
                   <div class="flex items-center gap-2">
                       <flux:icon.arrow-trending-up variant="micro"
                           class="{{ $inventoryGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}" />
                       <span class="text-sm {{ $inventoryGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                           {{ $inventoryGrowth }}%
                       </span>
                   </div>
               @endisland
           </flux:card>


           <!-- Low Stock -->
           <flux:card>
               <flux:text>Low Stock Products</flux:text>
               <flux:heading size="xl" class="mb-1">
                   {{ $lowStock }}
               </flux:heading>
               @if ($lowStock)
                   <flux:text class="text-red-600">Products below 10 units</flux:text>
               @endif

           </flux:card>
       </div>

       <!-- Charts -->
       <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
           <!-- Revenue Chart -->
           <flux:card>
               @island(lazy: true, always: true)
                   @placeholder
                       <flux:skeleton class="h-82 w-full" />
                   @endplaceholder
                   <flux:heading class="p-4">
                       Revenue ({{ $year }})
                   </flux:heading>
                   <x-charts.graph :data="$revenueData" x-label="Month" y-label="Revenue ($)" line-color="#3B82F6"
                       wire:key="{{ rand() }}" />
               @endisland
           </flux:card>

           <!-- Products Added Chart -->
           <flux:card>

               @island(lazy: true, always: true)
                   @placeholder
                       <flux:skeleton class="h-82 w-full" />
                   @endplaceholder
                   <flux:heading class="p-4">
                       Products Added ({{ $year }})
                   </flux:heading>
                   <flux:charts.graph type="bar" :data="$productsData" x-label="Month" y-label="Products"
                       bar-color="#10B981" show-values wire:key="{{ rand() }}" />
               @endisland

           </flux:card>

           <!-- Invoices Status Chart -->
           <flux:card>
               @island(lazy: true, always: true)
                   @placeholder
                       <div class="h-82 w-full grid place-items-center ">
                           <flux:skeleton class="size-72 rounded-full" />
                       </div>
                   @endplaceholder
                   <flux:heading class="p-4">Invoices Status ({{ $year }})</flux:heading>
                   <x-charts.pie class="max-h-96" :data="count($invoicesData) > 0
                       ? $invoicesData
                       : [
                           [
                               'label' => 'No data',
                               'value' => 100,
                               'color' => '#ccc',
                           ],
                       ]" show-legend show-values
                       wire:key="{{ rand() }}" />
               @endisland

           </flux:card>
       </div>
   </div>
