<x-admin-layout>
    <div class="col-md-12 pt-2 mt-2 container">
        <div class="space-y-8">
          
          <!-- Welcome Hero Banner -->
          <section class="relative overflow-hidden rounded-3xl border border-slate-900 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-8 md:p-10 shadow-2xl">
            <div class="absolute top-[-30%] right-[-5%] w-96 h-96 rounded-full bg-indigo-500/10 blur-[120px] pointer-events-none"></div>
            
            <div class="relative z-10 max-w-3xl space-y-3">
              <span class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 rounded-full px-3 py-1">
                <i class="fa fa-server"></i> Moodle System Tools
              </span>
              <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white leading-tight">
                Moodle Integration &amp; Migration Console
              </h2>
              <p class="text-slate-400 text-sm md:text-base leading-relaxed">
                Run the Moodle schema upgrades, URL rewriting, cache purging, and sync both Joomla website and old Moodle users/instructors directly to the unified SITS portal.
              </p>
            </div>
          </section>

          <!-- Main Actions & Console Panel -->
          <div class="grid lg:grid-cols-3 gap-8">
              
              <!-- Action Buttons -->
              <div class="lg:col-span-1 space-y-6">
                  <div class="card p-6 bg-slate-900/35 border border-slate-900 rounded-2xl shadow-xl space-y-4">
                      <h4 class="text-lg font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                          <i class="fa fa-play text-indigo-400"></i> Moodle Database Migration
                      </h4>
                      <div class="space-y-3">
                          <button onclick="runTool('moodle-recon')" class="w-full text-left flex items-center justify-between px-4 py-3 rounded-xl border border-slate-800 bg-slate-950 hover:bg-slate-900 hover:border-indigo-500/20 text-slate-300 font-medium transition group">
                              <span><i class="fa fa-search mr-2 text-indigo-400"></i> Phase A: Run Recon (Dry)</span>
                              <i class="fa fa-chevron-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                          </button>
                          
                          <button onclick="runTool('moodle-migrate')" class="w-full text-left flex items-center justify-between px-4 py-3 rounded-xl border border-slate-800 bg-slate-950 hover:bg-slate-900 hover:border-emerald-500/20 text-slate-300 font-medium transition group">
                              <span><i class="fa fa-database mr-2 text-emerald-400"></i> Phase C: Run Migration (Clone &amp; Up)</span>
                              <i class="fa fa-chevron-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                          </button>

                          <button onclick="runTool('moodle-fix-old')" class="w-full text-left flex items-center justify-between px-4 py-3 rounded-xl border border-slate-800 bg-slate-950 hover:bg-slate-900 hover:border-amber-500/20 text-slate-300 font-medium transition group">
                              <span><i class="fa fa-link mr-2 text-amber-400"></i> Phase D: Re-point Old Moodle</span>
                              <i class="fa fa-chevron-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                          </button>
                      </div>
                  </div>

                  <div class="card p-6 bg-slate-900/35 border border-slate-900 rounded-2xl shadow-xl space-y-4">
                      <h4 class="text-lg font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                          <i class="fa fa-users text-violet-400"></i> User Synchronization
                      </h4>
                      <div class="space-y-3">
                          <button onclick="runTool('import-moodle-users')" class="w-full text-left flex items-center justify-between px-4 py-3 rounded-xl border border-slate-800 bg-slate-950 hover:bg-slate-900 hover:border-violet-500/20 text-slate-300 font-medium transition group">
                              <span><i class="fa fa-user-graduate mr-2 text-violet-400"></i> Import Moodle Users</span>
                              <i class="fa fa-chevron-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                          </button>

                          <button onclick="runTool('import-joomla-users')" class="w-full text-left flex items-center justify-between px-4 py-3 rounded-xl border border-slate-800 bg-slate-950 hover:bg-slate-900 hover:border-orange-500/20 text-slate-300 font-medium transition group">
                              <span><i class="fa fa-user-plus mr-2 text-orange-400"></i> Import Joomla Users</span>
                              <i class="fa fa-chevron-right text-slate-600 group-hover:translate-x-1 transition-transform"></i>
                          </button>
                      </div>
                  </div>
              </div>

              <!-- Output Console Window -->
              <div class="lg:col-span-2">
                  <div class="card h-full flex flex-col bg-slate-950 border border-slate-900 rounded-2xl shadow-2xl overflow-hidden min-h-[450px]">
                      <div class="bg-slate-900 px-5 py-3 border-b border-slate-800/60 flex items-center justify-between">
                          <h4 class="text-sm font-bold text-white flex items-center gap-2">
                              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                              System Output Logs
                          </h4>
                          <button onclick="clearConsole()" class="text-xs text-slate-500 hover:text-slate-300 font-semibold transition">
                              <i class="fa fa-trash-can mr-1"></i> Clear Log
                          </button>
                      </div>
                      <div class="flex-grow p-5 bg-black overflow-y-auto font-mono text-xs text-emerald-400 leading-relaxed scrollbar-thin scrollbar-thumb-slate-800" style="max-height: 480px;">
                          <pre id="console-output" class="whitespace-pre-wrap select-all">System ready. Click any action to execute command...</pre>
                      </div>
                  </div>
              </div>

          </div>

        </div>
    </div>

    <!-- JavaScript Execution Logic -->
    <script>
        function runTool(action) {
            const consoleBox = document.getElementById('console-output');
            consoleBox.textContent = `Executing [${action}] on server... please wait...\n\n`;
            
            fetch(`/admin/moodle-tools/run/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.output) {
                    consoleBox.textContent += data.output;
                } else if (data.error) {
                    consoleBox.textContent += `Error: ${data.error}`;
                } else {
                    consoleBox.textContent += 'Done with no output response.';
                }
            })
            .catch(error => {
                consoleBox.textContent += `AJAX Execution Error: ${error.message}`;
            });
        }

        function clearConsole() {
            document.getElementById('console-output').textContent = 'Console cleared. Ready.';
        }
    </script>
</x-admin-layout>
