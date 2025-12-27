import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Flex, FlexItem } from '@wordpress/components';
import { useRef } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';

const FeaturedVideo = () => {
    const postType = useSelect(select => select('core/editor').getCurrentPostType(), []);

    const hasPostThumbnailSupport = useSelect(
        select => {
            const postTypeObject = select(coreStore).getPostType(postType);
            return postTypeObject?.supports?.['thumbnail'] ?? false;
        },
        [postType]
    );

    if (!hasPostThumbnailSupport) {
        return null;
    }

    const META_KEY = '_bfvr_featured_video_id';
    const [meta, setMeta] = useEntityProp('postType', postType, 'meta');
    const selectedVideoId = meta?.[META_KEY] || '';
    const toggleRef = useRef();
    const mediaSourceUrl = useSelect(
        select => (selectedVideoId ? select('core').getMedia(selectedVideoId)?.source_url : null),
        [selectedVideoId]
    );

    const onRemoveVideo = () => {
        setMeta({ [META_KEY]: null });
    };

    const setVideoSelection = media => {
        setMeta({ [META_KEY]: media.id });
    };

    // Check if the current post type is enabled for featured video from localized settings
    const enabledPostTypes = window.BFVRSettings?.enabledPostTypes || [];
    if (!enabledPostTypes.includes(postType)) {
        return null;
    }

    return (
        <PluginDocumentSettingPanel
            name="binsaif-featured-video-replacer-control"
            title={__('Featured Video', 'binsaif-featured-video-replacer')}
            className="video-metabox"
        >
            <MediaUploadCheck>
                <MediaUpload
                    onSelect={setVideoSelection}
                    allowedTypes={['video']}
                    value={selectedVideoId}
                    render={({ open }) => (
                        <div className="editor-post-featured-image__container">
                            <Flex alignment="center" direction="column">
                                <FlexItem>
                                    <Button
                                        ref={toggleRef}
                                        className={
                                            !selectedVideoId ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview'
                                        }
                                        onClick={open}
                                    >
                                        {!selectedVideoId ? (
                                            __('Set featured video', 'binsaif-featured-video-replacer')
                                        ) : (
                                            <video
                                                className="editor-post-featured-image__preview-video"
                                                controls
                                                src={mediaSourceUrl}
                                                poster={mediaSourceUrl}
                                                alt={__('Selected Video', 'binsaif-featured-video-replacer')}
                                            />
                                        )}
                                    </Button>
                                </FlexItem>
                                {selectedVideoId && (
                                    <FlexItem>
                                        <Flex align="center">
                                            <FlexItem>
                                                <Button __next40pxDefaultSize variant="secondary" onClick={open} width="100%">
                                                    {__('Replace', 'binsaif-featured-video-replacer')}
                                                </Button>
                                            </FlexItem>
                                            <FlexItem>
                                                <Button
                                                    __next40pxDefaultSize
                                                    variant="secondary"
                                                    width="100%"
                                                    onClick={() => {
                                                        onRemoveVideo();
                                                        toggleRef.current.focus();
                                                    }}
                                                >
                                                    {__('Remove', 'binsaif-featured-video-replacer')}
                                                </Button>
                                            </FlexItem>
                                        </Flex>
                                    </FlexItem>
                                )}
                            </Flex>
                        </div>
                    )}
                />
            </MediaUploadCheck>
        </PluginDocumentSettingPanel>
    );
};

registerPlugin('bfvr-dynamic-featured-video', {
    render: FeaturedVideo
});
