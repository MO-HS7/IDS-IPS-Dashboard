import { usePage } from '@inertiajs/vue3';

export function useFeature() {
    const page = usePage();

    const feature = (name, defaultEnabled = false) => {
        const features = page.props.features || {};
        return features[name] === true || (features[name] === undefined && defaultEnabled);
    };

    return { feature };
}
