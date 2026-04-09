<?php

namespace Laravel\Nova\LogViewer\Http\Controllers\Pages;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File as FileFacade;
use Inertia\Inertia;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\LogViewer\File;
use Symfony\Component\Finder\SplFileInfo;

class LogViewerController extends Controller
{
    /**
     * Show the log viewer index.
     *
     * @return \Inertia\Response
     */
    public function __invoke()
    {
        $logs = collect(FileFacade::allFiles(storage_path('logs')))
            ->filter(fn (SplFileInfo $log) => $log->getExtension() === 'log')
            ->map(function (SplFileInfo $log) {
                return [
                    'label' => $log->getRelativePathname(),
                    'value' => $log->getRelativePathname(),
                ];
            })
            ->sortByDesc('label')
            ->values();

        return Inertia::render('NovaLogViewer', [
            'logs' => $logs,
        ]);
    }

    /**
     * Fetch the latest content for a log.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetch(NovaRequest $request)
    {
        $request->validate(['lastLine' => ['numeric']]);
        $logFile = new File(storage_path('logs/' . $request->log));
        $lines = $logFile->contentAfterLine($request->lastLine);
        $lastLine = $request->lastLine + substr_count($lines, PHP_EOL);

        return response()->json([
            'lastLine' => $lastLine,
            'content' => $lines,
            'numberOfLines' => $logFile->numberOfLines(),
        ]);
    }

    /**
     * Download the given log file as .txt.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(NovaRequest $request)
    {
        $request->validate(['log' => ['required', 'string']]);

        $path = storage_path('logs/' . $request->log);

        abort_unless(
            FileFacade::exists($path) && str_ends_with($request->log, '.log'),
            404
        );

        $downloadName = pathinfo($request->log, PATHINFO_FILENAME) . '.txt';

        return response()->download($path, $downloadName, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
