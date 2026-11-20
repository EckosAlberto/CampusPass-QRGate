export default function AppLogo() {
    return (
        <div className="flex h-14 w-full items-center overflow-hidden rounded-md bg-white px-1.5 group-data-[collapsible=icon]:h-9 group-data-[collapsible=icon]:w-9">
            <img
                src="/images/logo-itt.png"
                alt="Tecnológico Nacional de México — Instituto Tecnológico de Tepic"
                className="h-11 w-auto max-w-none shrink-0 group-data-[collapsible=icon]:h-7"
            />
        </div>
    );
}
