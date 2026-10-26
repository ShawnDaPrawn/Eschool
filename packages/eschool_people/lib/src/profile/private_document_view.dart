import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:flutter/material.dart';

bool _alwaysActive() => true;

/// Private authenticated bytes are owned by this reader, never a global cache.
/// An enclosing context owner must dispose the reader when its authority changes.
class PrivateDocumentView extends StatefulWidget {
  const PrivateDocumentView({
    required this.identity,
    required this.name,
    required this.mime,
    required this.loadBytes,
    this.isActive = _alwaysActive,
    this.onClose,
    this.embedded = false,
    this.maximumBytes = 5 * 1024 * 1024,
    super.key,
  });
  final Object identity;
  final String name, mime;
  final Future<Uint8List> Function() loadBytes;
  final bool Function() isActive;
  final VoidCallback? onClose;
  final bool embedded;
  final int maximumBytes;
  @override
  State<PrivateDocumentView> createState() => _PrivateDocumentViewState();
}

class _PrivateDocumentViewState extends State<PrivateDocumentView>
    with WidgetsBindingObserver {
  Uint8List? bytes;
  ui.Image? image;
  ApiException? error;
  bool pdfReady = false, suspended = false;
  int generation = 0;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    load();
  }

  @override
  void didUpdateWidget(PrivateDocumentView oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.identity != oldWidget.identity ||
        widget.mime != oldWidget.mime ||
        widget.name != oldWidget.name) {
      generation++;
      clearBytes();
      error = null;
      if (!suspended) load();
    }
  }

  void clearBytes() {
    image?.dispose();
    image = null;
    bytes?.fillRange(0, bytes!.length, 0);
    bytes = null;
    pdfReady = false;
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) {
      generation++;
      suspended = true;
      clearBytes();
      if (mounted) setState(() => error = null);
    } else {
      suspended = false;
      if (mounted) {
        setState(() {});
        load();
      }
    }
  }

  Future<void> load() async {
    final epoch = ++generation;
    Uint8List? result;
    ui.Codec? codec;
    try {
      result = await widget.loadBytes();
      if (!mounted || suspended || epoch != generation || !widget.isActive()) {
        result.fillRange(0, result.length, 0);
        return;
      }
      if (result.isEmpty || result.length > widget.maximumBytes) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      if (![
        'application/pdf',
        'image/jpeg',
        'image/png',
      ].contains(widget.mime)) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      if (widget.mime != 'application/pdf') {
        codec = await ui.instantiateImageCodec(
          result,
          targetWidth: 1024,
          allowUpscaling: false,
        );
        final frame = await codec.getNextFrame();
        if (!mounted ||
            suspended ||
            epoch != generation ||
            !widget.isActive()) {
          frame.image.dispose();
          result.fillRange(0, result.length, 0);
          return;
        }
        image = frame.image;
      }
      setState(() => bytes = result);
    } catch (failure) {
      result?.fillRange(0, result.length, 0);
      if (mounted && !suspended && epoch == generation) {
        setState(() {
          error = failure is ApiException
              ? failure
              : ApiException(ApiErrorCodes.defaultErrorMessageKey);
        });
      }
    } finally {
      codec?.dispose();
    }
  }

  @override
  void dispose() {
    generation++;
    WidgetsBinding.instance.removeObserver(this);
    clearBytes();
    super.dispose();
  }

  void retry() {
    image?.dispose();
    bytes?.fillRange(0, bytes!.length, 0);
    setState(() {
      error = null;
      bytes = null;
      image = null;
      pdfReady = false;
    });
    load();
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.isActive()) clearBytes();
    final epoch = generation;
    final content = suspended || !widget.isActive()
        ? const SizedBox.shrink()
        : error != null
        ? LoadFailure(error: error!, onRetry: retry)
        : bytes == null
        ? const Center(child: CircularProgressIndicator())
        : widget.mime == 'application/pdf'
        ? Stack(
            children: [
              PdfDocumentView(
                key: ValueKey((widget.identity, epoch)),
                bytes: bytes,
                onLoaded: () {
                  if (mounted &&
                      !suspended &&
                      widget.isActive() &&
                      epoch == generation) {
                    setState(() {
                      pdfReady = true;
                    });
                  }
                },
                onError: () {
                  if (mounted &&
                      !suspended &&
                      widget.isActive() &&
                      epoch == generation) {
                    setState(() {
                      error = ApiException(
                        ApiErrorCodes.defaultErrorMessageKey,
                      );
                    });
                  }
                },
              ),
              if (!pdfReady) const Center(child: CircularProgressIndicator()),
              if (pdfReady) const SizedBox(key: ValueKey('profile-file-ready')),
            ],
          )
        : InteractiveViewer(
            child: Center(
              child: Semantics(
                image: true,
                label: widget.name,
                child: RawImage(image: image, fit: BoxFit.contain),
              ),
            ),
          );
    if (widget.embedded) {
      return Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    widget.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                ),
                if (widget.onClose != null)
                  IconButton(
                    tooltip: MaterialLocalizations.of(
                      context,
                    ).closeButtonTooltip,
                    onPressed: widget.onClose,
                    icon: const Icon(Icons.close),
                  ),
              ],
            ),
          ),
          Expanded(child: content),
        ],
      );
    }
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.name),
        leading: widget.onClose == null
            ? null
            : BackButton(onPressed: widget.onClose),
      ),
      body: content,
    );
  }
}
