<?php

namespace App\Http\Controllers;

use App\Models\MLModel;
use App\Models\MLTrainingSession;
use App\Services\MLTrainingService;
use App\Jobs\TrainMLModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Class MLModelController
 *
 * Handles CRUD operations for ML Models, including listing, creation, editing, and deletion.
 *
 * @package App\Http\Controllers
 */
class MLModelController extends Controller
{
    /**
     * Display a listing of ML Models.
     *
     * @OA\Get(
     *     path="/ml-models",
     *     summary="List all ML Models",
     *     tags={"MLModels"},
     *     @OA\Response(
     *         response=200,
     *         description="ML Models retrieved successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $mlModels = MLModel::latest()->paginate(10);

        return Inertia::render('MLModels/Index', [
            'mlModels' => $mlModels
        ]);
    }

    /**
     * Show the form for creating a new ML Model.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('MLModels/Create');
    }

    /**
     * Store a newly created ML Model in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ml_models,name',
                'description' => 'nullable|string|max:1000',
                'file_path' => 'nullable|string|max:255',
                'model_file' => 'nullable|file|mimes:pkl,joblib,h5|max:51200',
            ]);

            if (empty($validated['file_path']) && !$request->hasFile('model_file')) {
                return back()->withErrors(['file_path' => 'Please provide a model file or a file path.'])->withInput();
            }

            if ($request->hasFile('model_file')) {
                $file = $request->file('model_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;
                $path = $file->storeAs('models', $filename, 'public');
                $validated['file_path'] = $path;
            }

            $mlModel = MLModel::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
            ]);

            Log::info('MLModel created successfully', ['id' => $mlModel->id]);

            return redirect()->route('ml-models.index')
                ->with('success', 'ML Model created successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to create MLModel: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create ML Model: ' . $e->getMessage()])
                         ->withInput();
        }
    }

    /**
     * Display the specified ML Model.
     *
     * @param MLModel $mlModel
     * @return \Inertia\Response
     */
    public function show(MLModel $mlModel)
    {
        return Inertia::render('MLModels/Show', [
            'mlModel' => $mlModel->load('latestMetric', 'latestTrainingSession')
        ]);
    }

    /**
     * Show the form for editing the specified ML Model.
     *
     * @param MLModel $mlModel
     * @return \Inertia\Response
     */
    public function edit(MLModel $mlModel)
    {
        return Inertia::render('MLModels/Edit', [
            'mlModel' => $mlModel
        ]);
    }

    /**
     * Update the specified ML Model in storage.
     *
     * @param Request $request
     * @param MLModel $mlModel
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, MLModel $mlModel)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:ml_models,name,' . $mlModel->id,
                'description' => 'nullable|string|max:1000',
                'file_path' => 'nullable|string|max:255',
                'model_file' => 'nullable|file|mimes:pkl,joblib,h5|max:51200',
            ]);

            if ($request->hasFile('model_file')) {
                $file = $request->file('model_file');
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;
                $path = $file->storeAs('models', $filename, 'public');
                $validated['file_path'] = $path;
            }

            $mlModel->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'file_path' => $validated['file_path'] ?? $mlModel->file_path,
            ]);

            Log::info('MLModel updated successfully', ['id' => $mlModel->id]);

            return redirect()->route('ml-models.index')
                ->with('success', 'ML Model updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update MLModel: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update ML Model: ' . $e->getMessage()])
                         ->withInput();
        }
    }

    /**
     * Remove the specified ML Model from storage.
     *
     * @param MLModel $mlModel
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(MLModel $mlModel)
    {
        try {
            $mlModel->delete();
            Log::info('MLModel deleted successfully', ['id' => $mlModel->id]);

            return redirect()->route('ml-models.index')
                ->with('success', 'ML Model deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete MLModel: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete ML Model: ' . $e->getMessage()]);
        }
    }

    /**
     * Show training form
     */
    public function train(MLModel $mlModel, MLTrainingService $service)
    {
        return Inertia::render('MLModels/Train', [
            'mlModel' => $mlModel->load('latestTrainingSession', 'latestMetric'),
            'modelTypes' => $service->getAvailableModelTypes(),
            'trainingSessions' => $mlModel->trainingSessions()->latest()->take(5)->get(),
        ]);
    }

    /**
     * Start model training
     */
    public function startTraining(Request $request, MLModel $mlModel, MLTrainingService $service)
    {
        try {
            $validated = $request->validate([
                'model_type' => 'required|string|in:random_forest,neural_network,svm,decision_tree,naive_bayes',
                'hyperparameters' => 'nullable|array',
                'dataset_path' => 'nullable|string',
            ]);

            // Get default hyperparameters if not provided
            $hyperparameters = $validated['hyperparameters'] ?? 
                $service->getDefaultHyperparameters($validated['model_type']);

            // Create training session
            $session = $service->startTraining(
                $mlModel,
                Auth::id(),
                $validated['model_type'],
                $hyperparameters,
                $validated['dataset_path'] ?? null
            );

            // Dispatch training job
            dispatch(new TrainMLModel($session));

            return response()->json([
                'success' => true,
                'message' => 'Training started successfully',
                'session' => $session,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start training', [
                'model_id' => $mlModel->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start training: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get training status
     */
    public function trainingStatus(MLModel $mlModel, string $sessionId)
    {
        $session = MLTrainingSession::where('session_id', $sessionId)
            ->where('ml_model_id', $mlModel->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'session' => $session,
            'model' => $mlModel->fresh(),
        ]);
    }

    /**
     * Get model metrics
     */
    public function metrics(MLModel $mlModel)
    {
        $metrics = $mlModel->metrics()->with('trainingSession')->latest()->get();
        $latestMetric = $mlModel->latestMetric;

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
            'latest' => $latestMetric,
        ]);
    }

    /**
     * Activate model
     */
    public function activate(MLModel $mlModel)
    {
        try {
            if (!$mlModel->isTrained()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot activate untrained model',
                ], 422);
            }

            $mlModel->activate();

            return response()->json([
                'success' => true,
                'message' => 'Model activated successfully',
                'model' => $mlModel->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate model: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get model predictions history
     */
    public function predictions(MLModel $mlModel)
    {
        $predictions = $mlModel->predictions()
            ->with('networkLog')
            ->latest('predicted_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'predictions' => $predictions,
        ]);
    }

    /**
     * Get available model types
     */
    public function modelTypes(MLTrainingService $service)
    {
        return response()->json([
            'success' => true,
            'types' => $service->getAvailableModelTypes(),
        ]);
    }
}
