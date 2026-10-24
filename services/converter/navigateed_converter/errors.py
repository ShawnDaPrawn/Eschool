class ConversionError(Exception):
    """A public, non-sensitive failure code; never include parser/source details."""
    def __init__(self, code: str, status: int = 422):
        super().__init__(code)
        self.code = code
        self.status = status
