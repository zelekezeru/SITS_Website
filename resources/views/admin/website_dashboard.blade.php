<x-admin-layout>
    <div class="col-md-12 pt-2 mt-2 container">
        <div class="space-y-8">
          
          <!-- Welcome Hero Banner -->
          <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8 md:p-10 shadow-2xl">
            <div class="absolute top-[-30%] right-[-5%] w-96 h-96 rounded-full bg-indigo-500/10 blur-[120px] pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl space-y-3">
              <span class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 rounded-full px-3 py-1">
                <i class="fa fa-globe"></i> Website Administrator
              </span>
              <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white leading-tight">
                Website Content &amp; Registries Console
              </h2>
              <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                Manage course listings, news blogs, events, academic programs, and website contacts using this modern management dashboard.
              </p>
            </div>
          </section>

          <!-- CMS Grid of Quick Actions & Stats -->
          <section class="space-y-4 pt-4">
            <h3 class="text-lg font-bold flex items-center gap-2 text-white">
              <i class="fa fa-dashboard text-indigo-400"></i>
              Management Modules
            </h3>

            @php
              $modules = [
                ['label' => 'Courses', 'countKey' => 'courses', 'path' => route('courses.list'), 'icon' => 'fa-book-open', 'color' => 'text-violet-400 border-violet-500/20 bg-violet-500/10'],
                ['label' => 'Blogs', 'countKey' => 'blogs', 'path' => route('blogs.list'), 'icon' => 'fa-newspaper', 'color' => 'text-amber-400 border-amber-500/20 bg-amber-500/10'],
                ['label' => 'Programs', 'countKey' => 'programs', 'path' => route('programs.list'), 'icon' => 'fa-graduation-cap', 'color' => 'text-cyan-400 border-cyan-500/20 bg-cyan-500/10'],
                ['label' => 'Events', 'countKey' => 'events', 'path' => route('events.list'), 'icon' => 'fa-calendar-days', 'color' => 'text-emerald-400 border-emerald-500/20 bg-emerald-500/10'],
                ['label' => 'Gallery', 'countKey' => 'gallery', 'path' => route('galleries.list'), 'icon' => 'fa-image', 'color' => 'text-pink-400 border-pink-500/20 bg-pink-500/10'],
                ['label' => 'Trainers', 'countKey' => 'trainers', 'path' => route('trainers.list'), 'icon' => 'fa-briefcase', 'color' => 'text-rose-400 border-rose-500/20 bg-rose-500/10'],
                ['label' => 'Libraries', 'countKey' => 'libraries', 'path' => route('libraries.list'), 'icon' => 'fa-book', 'color' => 'text-teal-400 border-teal-500/20 bg-teal-500/10'],
                ['label' => 'Library Subscriptions', 'countKey' => 'library_subscriptions', 'path' => route('library.subscriptions'), 'icon' => 'fa-book-open', 'color' => 'text-purple-400 border-purple-500/20 bg-purple-500/10'],
                ['label' => 'Users', 'countKey' => 'users', 'path' => route('users.list'), 'icon' => 'fa-users', 'color' => 'text-blue-400 border-blue-500/20 bg-blue-500/10'],
                ['label' => 'Contacts', 'countKey' => 'contacts', 'path' => route('contacts.list'), 'icon' => 'fa-folder-open', 'color' => 'text-orange-400 border-orange-500/20 bg-orange-500/10'],
                ['label' => 'Subscriptions', 'countKey' => 'subscriptions', 'path' => route('subscriptions.index'), 'icon' => 'fa-folder-open', 'color' => 'text-indigo-400 border-indigo-500/20 bg-indigo-500/10'],
              ];
            @endphp

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              @foreach($modules as $m)
              <a 
                href="{{ $m['path'] }}"
                class="group relative rounded-2xl border border-slate-900 bg-slate-900/35 backdrop-blur-md p-6 hover:border-indigo-500/30 hover:bg-slate-900/60 transition-all flex flex-col justify-between"
              >
                <div>
                  <div class="flex justify-between items-start">
                    <span class="w-12 h-12 rounded-xl flex items-center justify-center text-lg border {{ $m['color'] }}">
                      <i class="fa {{ $m['icon'] }}"></i>
                    </span>
                    <span class="text-2xl font-extrabold text-white font-outfit">
                      {{ $stats[$m['countKey']] ?? 0 }}
                    </span>
                  </div>
                  
                  <h4 class="text-base font-bold text-white mt-4 group-hover:text-indigo-300 transition-colors">
                    {{ $m['label'] }}
                  </h4>
                  <p class="text-xs text-slate-500 mt-1">
                    View lists, edit details, and add new registry database items.
                  </p>
                </div>

                <div class="border-t border-slate-800/40 pt-4 mt-4 flex items-center justify-between text-xs font-semibold text-indigo-400 group-hover:text-indigo-300">
                  <span>Manage Items</span>
                  <i class="fa fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
              </a>
              @endforeach
            </div>
          </section>

        </div>
    </div>
</x-admin-layout>
