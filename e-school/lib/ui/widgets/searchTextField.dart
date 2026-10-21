import 'dart:async';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';

class CustomSearchTextField extends StatefulWidget {
  const CustomSearchTextField({
    required this.textController,
    required this.onSearch,
    required this.onTextClear,
    super.key,
  });
  final TextEditingController textController;
  final Function(String) onSearch;
  final Function() onTextClear;

  @override
  State<CustomSearchTextField> createState() => _CustomSearchTextFieldState();
}

class _CustomSearchTextFieldState extends State<CustomSearchTextField> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  Timer? _debounce;

  @override
  void dispose() {
    _debounce?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: TextFormField(
        controller: widget.textController,
        autofocus: true,
        decoration: InputDecoration(
          hintStyle: const TextStyle(fontSize: 14),
          hintText: UiUtils.getTranslatedLabel(context, searchHintKey),
          fillColor: Theme.of(context).colorScheme.surface,
          filled: true,
          contentPadding: const EdgeInsets.all(5),
          prefixIcon: Icon(
            Icons.search,
            color: Theme.of(context).colorScheme.primary,
          ),
          suffixIcon: IconButton(
            icon: Icon(
              Icons.clear,
              color: Theme.of(context).colorScheme.primary,
            ),
            onPressed: () {
              if (_debounce?.isActive ?? false) _debounce?.cancel();
              widget.onTextClear();
              widget.textController.clear();
              _formKey.currentState!.validate();
            },
          ),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(25)),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(25),
            borderSide: BorderSide(color: Theme.of(context).colorScheme.error),
          ),
          errorStyle: TextStyle(
            color: Theme.of(context).colorScheme.onInverseSurface,
          ),
        ),
        validator: (value) {
          if (value == null || value.trim().isEmpty) {
            return null;
          } else if (value.trim().length < 3) {
            return UiUtils.getTranslatedLabel(
              context,
              addMoreCharactorsToSearchKey,
            );
          } else {
            return null;
          }
        },
        onChanged: (value) {
          if (_debounce?.isActive ?? false) _debounce?.cancel();
          _debounce = Timer(const Duration(milliseconds: 500), () {
            if (_formKey.currentState!.validate()) {
              if (widget.textController.text.trim().isNotEmpty) {
                widget.onSearch(widget.textController.text);
              }
            } else {
              widget.onTextClear();
            }
          });
        },
      ),
    );
  }
}
